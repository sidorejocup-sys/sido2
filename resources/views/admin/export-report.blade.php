<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ ucfirst(str_replace('_', ' ', $module)) }} Export</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1f2937; background: #f9fafb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #d1d5db; padding: 8px 10px; }
        th { background: #111827; color: #f9fafb; text-align: left; }
        tr:nth-child(even) { background: #f3f4f6; }
        h1 { font-size: 18px; margin-bottom: 0; }
        .header { margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ ucfirst(str_replace('_', ' ', $module)) }} Export</h1>
        <p>Generated on {{ now()->format('Y-m-d H:i') }}</p>
    </div>
    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($headers as $field)
                        <td>{{ $row->{$field} }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
