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

        table {
            border: none;
            border-collapse: collapse;
        }

        td,
        th {
            border: none;
        }
    </style>
</head>

<body>
    <table width="100%" style="margin-bottom: 20px; border: none; border-collapse: collapse;">
        <tr>
            <td style="text-align: left; border: none;">
                <h2>Hóa đơn #{{ $booking->id }}</h2>
            </td>
            <td style="text-align: right; border: none;" class="logo">
                <img src="{{ public_path('images/logo.png') }}" alt="Logo">
            </td>
        </tr>
    </table>


    <table>
        <tr>
            <td>
                <strong>Onboarding</strong><br>
                contact@roomrise.vn<br>
                0999999999
            </td>
            <td>
                <strong>{{ $booking->customer->full_name }}</strong><br>
                {{ $booking->customer->email }}<br>
                {{ $booking->customer->phone }}
            </td>
        </tr>
    </table>
    <table width="100%" cellpadding="6" cellspacing="0" style="margin-top: 20px;">
        <tr>
            <td colspan="2" style="font-weight: bold; font-size: 16px; padding-bottom: 10px;">
                Chi tiết đặt phòng
            </td>
        </tr>
        <tr>
            <td style="width: 50%;">Nhận phòng</td>
            <td>
                {{ \Carbon\Carbon::parse($booking->check_in_date)->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td>Trả phòng</td>
            <td>
                {{ \Carbon\Carbon::parse($booking->check_out_date)->format('d/m/Y') }}
            </td>
        </tr>
        <tr>
            <td>Phương thức thanh toán tiền phòng</td>
            <td>
                @if ($booking->payment_type === 'Hotel Collect')
                    Thu tại chỗ nghỉ (Khách trực tiếp)
                @elseif ($booking->payment_type === 'Partner Collect')
                    Thu bởi đối tác (Đối tác)
                @elseif ($booking->payment_type === 'OTA Collect')
                    Thu bởi OTA (OTA)
                @else
                    Không xác định
                @endif
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="8" cellspacing="0" style="margin-top: 20px; border-collapse: collapse;">
        <thead style="background-color: #f8f8f8;">
            <tr>
                <th style="text-align: left;">#</th>
                <th style="text-align: left;">Loại Phòng</th>
                <th style="text-align: left;">Phòng</th>
                <th style="text-align: center;">Số đêm</th>
                <th style="text-align: right;">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($booking->bookingRooms as $i => $room)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $room->room->name }}</td>
                    <td>{{ $room->roomUnit->name }}</td>

                    <td style="text-align: center;">{{ $room->nights }} đêm</td>

                    <td style="text-align: right;">{{ number_format($room->total) }} ₫</td>
                </tr>
            @endforeach
        </tbody>
    </table>



    <table>
        @php
            $totalDiscount = $booking->bookingRooms->sum('discount');
        @endphp

        <tr>
            <td class="text-right bold" style="width: 80%;">Giảm giá:</td>
            <td class="text-right">{{ number_format($totalDiscount) }} ₫</td>
        </tr>
        <tr class="total-row">
            <td class="text-right">Tổng cộng:</td>
            <td class="text-right">{{ number_format($booking->customer_payment_amount) }} ₫</td>
        </tr>
    </table>

    <table width="100%" cellpadding="6" cellspacing="0" style="margin-top: 20px;">
        <tr>
            <td colspan="2" style="font-weight: bold; font-size: 16px; padding-bottom: 10px;">
                Thông tin thanh toán
            </td>
        </tr>
        <tr>
            <td style="width: 50%;">Cần thanh toán</td>
            <td>
                {{ number_format($booking->customer_payment_amount) }} ₫
            </td>
        </tr>
        <tr>
            <td>Đã thanh toán</td>
            <td>
                {{ number_format($booking->paid) }} ₫
            </td>
        </tr>
        <tr>
            <td>Chưa thanh toán</td>
            <td>
                {{ number_format($booking->remaining) }} ₫
            </td>
        </tr>
    </table>
    
    <hr>
    <p class="small-note text-center">In bởi: Demo</p>
    <p class="small-note text-center">Ngày in: {{ now()->format('d/m/Y H:i') }}</p>
    <p class="text-center small-note">Cung cấp bởi <strong>RoomRise</strong></p>
</body>

</html>
