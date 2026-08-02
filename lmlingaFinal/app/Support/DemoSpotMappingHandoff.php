<?php

namespace App\Support;

use App\Support\UiRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Short-lived, single-use Spot Mapping → Water Supply handoff tokens.
 *
 * Tokens bind to a server-owned DemoSpotMappingPlot record.
 * Browser-submitted plot payloads alone cannot mint a token.
 */
final class DemoSpotMappingHandoff
{
    public const SESSION_KEY = 'lml.demo.spot_mapping_handoff.v1';

    public const ACTOR_SESSION_KEY = 'lml.demo.handoff_actor_id.v1';

    /** Token lifetime in minutes. */
    public const TTL_MINUTES = 10;

    public const INVALID_MESSAGE = 'Unable to continue because the household plot session is invalid or expired. Please plot the household again.';

    /**
     * Stable actor key for the current request.
     * Prefer authenticated user id; otherwise bind to UI shell role + stable session actor id.
     */
    public static function actorKey(): string
    {
        $user = Auth::user();
        if ($user !== null) {
            $id = data_get($user, 'id');
            if ($id !== null && (string) $id !== '') {
                return 'user:'.(string) $id;
            }
        }

        if (! session()->has(self::ACTOR_SESSION_KEY)) {
            session([self::ACTOR_SESSION_KEY => (string) Str::uuid()]);
        }

        $role = UiRole::current() ?? UiRole::shellRole();

        return 'demo:'.(string) $role.':'.(string) session(self::ACTOR_SESSION_KEY);
    }

    /**
     * Create a trusted server plot from a validated plot request, then issue a handoff token.
     * Token fields are taken from the stored plot — not from raw browser authority.
     *
     * @param  array<string, mixed>  $plotRequest
     * @return array{handoff_token: string, plot_id: string}|null
     */
    public static function createPlotAndIssue(array $plotRequest): ?array
    {
        $plot = DemoSpotMappingPlot::create($plotRequest);
        if ($plot === null) {
            return null;
        }

        $token = self::issueForPlotId((string) $plot['plot_id']);
        if ($token === null) {
            return null;
        }

        return [
            'handoff_token' => $token,
            'plot_id' => (string) $plot['plot_id'],
        ];
    }

    /**
     * Issue a plaintext handoff token bound to an existing server-owned plot.
     * Stores only the token hash server-side.
     */
    public static function issueForPlotId(string $plotId): ?string
    {
        $plot = DemoSpotMappingPlot::findForActor($plotId);
        if ($plot === null) {
            return null;
        }

        if ((string) ($plot['status'] ?? '') !== DemoSpotMappingPlot::STATUS_CONFIRMED) {
            return null;
        }

        $marked = DemoSpotMappingPlot::markHandoffIssued($plotId);
        if ($marked === null) {
            return null;
        }

        $plaintext = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plaintext);
        $now = now();

        $tokens = self::all();
        $tokens[$hash] = [
            'token_hash' => $hash,
            'actor_id' => self::actorKey(),
            'plot_id' => (string) $marked['plot_id'],
            'household_no' => (string) $marked['household_no'],
            'created_at' => $now->toIso8601String(),
            'expires_at' => $now->copy()->addMinutes(self::TTL_MINUTES)->toIso8601String(),
            'consumed_at' => null,
        ];
        session([self::SESSION_KEY => $tokens]);

        return $plaintext;
    }

    /**
     * Consume a plaintext token (single-use).
     * Reloads and verifies the trusted plot record before returning link data.
     *
     * @return array<string, mixed>|null
     */
    public static function consume(string $plaintextToken): ?array
    {
        $plaintextToken = trim($plaintextToken);
        if ($plaintextToken === '' || ! preg_match('/^[a-f0-9]{64}$/', $plaintextToken)) {
            return null;
        }

        $hash = hash('sha256', $plaintextToken);
        $tokens = self::all();
        $record = $tokens[$hash] ?? null;

        if (! is_array($record)) {
            return null;
        }

        if ((string) ($record['actor_id'] ?? '') !== self::actorKey()) {
            return null;
        }

        if (! empty($record['consumed_at'])) {
            return null;
        }

        $expiresAt = (string) ($record['expires_at'] ?? '');
        if ($expiresAt === '' || now()->greaterThan(new \DateTimeImmutable($expiresAt))) {
            return null;
        }

        $plotId = (string) ($record['plot_id'] ?? '');
        $tokenHousehold = (string) ($record['household_no'] ?? '');
        if ($plotId === '' || $tokenHousehold === '') {
            return null;
        }

        $plot = DemoSpotMappingPlot::findForActor($plotId);
        if ($plot === null) {
            return null;
        }

        if ((string) ($plot['status'] ?? '') !== DemoSpotMappingPlot::STATUS_HANDOFF_ISSUED) {
            return null;
        }

        if ((string) ($plot['household_no'] ?? '') !== $tokenHousehold) {
            return null;
        }

        $linkedPlot = DemoSpotMappingPlot::markWaterSupplyLinked($plotId);
        if ($linkedPlot === null) {
            return null;
        }

        $record['consumed_at'] = now()->toIso8601String();
        $tokens[$hash] = $record;
        session([self::SESSION_KEY => $tokens]);

        // Return trusted plot fields (server store), not browser payload.
        return [
            'plot_id' => (string) $linkedPlot['plot_id'],
            'household_no' => (string) $linkedPlot['household_no'],
            'house_head' => (string) ($linkedPlot['house_head'] ?? ''),
            'household_type' => (string) ($linkedPlot['household_type'] ?? ''),
            'zone' => (string) ($linkedPlot['zone'] ?? ''),
            'lat' => $linkedPlot['lat'] ?? null,
            'lng' => $linkedPlot['lng'] ?? null,
            'actor_id' => (string) ($linkedPlot['actor_id'] ?? ''),
            'status' => (string) ($linkedPlot['status'] ?? ''),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function all(): array
    {
        /** @var array<string, array<string, mixed>> $tokens */
        $tokens = session(self::SESSION_KEY, []);

        return is_array($tokens) ? $tokens : [];
    }
}
