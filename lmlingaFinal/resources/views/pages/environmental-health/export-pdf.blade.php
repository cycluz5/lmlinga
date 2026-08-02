{{--
    Printable Environmental Health export (Save as PDF via browser print).
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Environmental Health Export - LMLinga</title>
    <style>
        :root {
            --lml-deep-green: #0b3d0b;
            --lml-primary: #22c55e;
        }
        body {
            margin: 0;
            padding: 1.5rem;
            color: #1f2937;
            font-family: "Segoe UI", Tahoma, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        h1 {
            margin: 0 0 0.25rem;
            color: var(--lml-deep-green);
            font-size: 1.35rem;
        }
        .meta {
            margin: 0 0 1rem;
            color: #6b7280;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .stat {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.5rem 0.65rem;
        }
        .stat strong {
            display: block;
            font-size: 1.1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 0.45rem 0.5rem;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #86efac;
            color: var(--lml-deep-green);
        }
        .actions {
            margin: 1rem 0;
        }
        .actions button {
            padding: 0.5rem 0.9rem;
            border: 0;
            border-radius: 999px;
            background: var(--lml-primary);
            color: #0b3d0b;
            font-weight: 700;
            cursor: pointer;
        }
        @media print {
            .actions { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <h1>Environmental Health Overview</h1>
    <p class="meta">
        Generated {{ $generatedAt->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
        · {{ count($rows) }} household amenities record(s)
    </p>

    <div class="actions">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="stats" aria-label="Summary statistics">
        <div class="stat"><span>Level I</span><strong>{{ $statistics['water_supply']['level_i'] }}</strong></div>
        <div class="stat"><span>Level II</span><strong>{{ $statistics['water_supply']['level_ii'] }}</strong></div>
        <div class="stat"><span>Level III</span><strong>{{ $statistics['water_supply']['level_iii'] }}</strong></div>
        <div class="stat"><span>Other Sources</span><strong>{{ $statistics['water_supply']['others'] }}</strong></div>
        <div class="stat"><span>Sanitary</span><strong>{{ $statistics['sanitation']['sanitary'] }}</strong></div>
        <div class="stat"><span>Unsanitary</span><strong>{{ $statistics['sanitation']['unsanitary'] }}</strong></div>
        <div class="stat"><span>Validated Water</span><strong>{{ $statistics['overview']['validated_water_sources'] }}</strong></div>
        <div class="stat"><span>Good Solid Waste</span><strong>{{ $statistics['overview']['good_solid_waste'] }}</strong></div>
    </div>

    <table>
        <caption class="visually-hidden" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);">
            Filtered environmental health household amenities export
        </caption>
        <thead>
            <tr>
                <th scope="col">HH No.</th>
                <th scope="col">HH Head</th>
                <th scope="col">Water Supply</th>
                <th scope="col">Sanitation</th>
                <th scope="col">Validation</th>
                <th scope="col">Overall</th>
                <th scope="col">Record</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['household_no'] }}</td>
                    <td>{{ $row['house_head'] }}</td>
                    <td>{{ $row['water_supply_label'] }}</td>
                    <td>{{ $row['toilet_status_label'] }}</td>
                    <td>{{ $row['validation_label'] }}</td>
                    <td>{{ $row['overall_label'] }}</td>
                    <td>{{ $row['record_status_label'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No records match the current filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script>
        window.addEventListener('load', function () {
            window.setTimeout(function () {
                window.print();
            }, 250);
        });
    </script>
</body>
</html>
