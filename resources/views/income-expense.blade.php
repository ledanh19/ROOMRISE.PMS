<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 14px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        th {
            background-color: #f9f9f9;
        }

        .logo img {
            width: 200px;
        }

        td,
        th {
            border: none;
        }
    </style>
</head>

<body>
    <table width="100%" style="margin-bottom: 20px;">
        <tr>
            <td style="text-align: left;">
                <h2>Phiếu {{ $incomeExpense->type === 'income' ? 'Thu' : 'Chi' }} #{{ $incomeExpense->id }}</h2>
            </td>
            <td style="text-align: right;" class="logo">
                @if (app()->runningInConsole() || request()->routeIs('income-expense.export-pdf'))
                    <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="width: 200px;">
                @else
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" style="width: 200px;">
                @endif
            </td>

        </tr>
    </table>

    <table>
        <tr>
            <td>
                <strong>Người tạo:</strong> {{ $incomeExpense->created_by ?? 'N/A' }}<br>
                <strong>Ngày tạo:</strong> {{ \Carbon\Carbon::parse($incomeExpense->created_at)->format('d/m/Y H:i') }}
            </td>
            <td>
                <strong>Đối tượng chi/trả:</strong> {{ $incomeExpense->payment_object }}<br>
                <strong>Nguồn thanh toán:</strong> {{ $incomeExpense->payment_source }}
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width: 50%;">Loại nghiệp vụ</td>
            <td>{{ $incomeExpense->category }} - {{ $incomeExpense->subcategory }}</td>
        </tr>
        <tr>
            <td>Phương thức thanh toán</td>
            <td>{{ $incomeExpense->payment_method }}</td>
        </tr>
        <tr>
            <td>Ngày chi / thu</td>
            <td>{{ \Carbon\Carbon::parse($incomeExpense->date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Trạng thái thanh toán</td>
            <td>{{ ucfirst($incomeExpense->payment_status) }}</td>
        </tr>
        <tr>
            <td>Ghi chú</td>
            <td>{{ $incomeExpense->note ?? '...' }}</td>
        </tr>
    </table>

    <table>
        <tr>
            <td class="text-right bold" style="width: 80%;">Số tiền:</td>
            <td class="text-right">{{ number_format($incomeExpense->amount) }} ₫</td>
        </tr>
    </table>

    @if ($incomeExpense->auditLogs && count($incomeExpense->auditLogs))
        <div class="mb-4">
            <h4>Lịch sử kiểm toán:</h4>
            <table>
                <thead>
                    <tr>
                        <th>Thao tác</th>
                        <th>Người thực hiện</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($incomeExpense->auditLogs as $log)
                        <tr>
                            <td>
                                @if ($log->action_type === 'create')
                                    Tạo phiếu
                                @elseif ($log->action_type === 'confirm_payment')
                                    Xác nhận thanh toán
                                @else
                                    {{ ucfirst($log->action_type) }}
                                @endif
                            </td>
                            <td>{{ $log->performed_by }}</td>
                            <td>{{ \Carbon\Carbon::parse($log->performed_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <hr>
    <p class="small-note text-center">In bởi: {{ auth()->user()->name }}</p>
    <p class="small-note text-center">Ngày in: {{ now()->format('d/m/Y H:i') }}</p>
    <p class="text-center small-note">Cung cấp bởi <strong>RoomRise</strong></p>
</body>

</html>
<script>
    window.onload = function() {
        window.print();
    };
</script>
