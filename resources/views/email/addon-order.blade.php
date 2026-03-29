<div style="font-family: Arial, Helvetica, sans-serif; color: #222; line-height: 1.6;">
    <h2 style="margin-bottom: 12px;">Order Add-on dari Portal Pelanggan</h2>

    <p style="margin: 0 0 12px;">Ada permintaan penambahan add-on baru dari pelanggan berikut:</p>

    <table cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 18px;">
        <tr>
            <td style="padding: 4px 12px 4px 0;"><strong>Nama</strong></td>
            <td style="padding: 4px 0;">{{ $customer->name }}</td>
        </tr>
        <tr>
            <td style="padding: 4px 12px 4px 0;"><strong>CID</strong></td>
            <td style="padding: 4px 0;">{{ $customer->customer_id }}</td>
        </tr>
        <tr>
            <td style="padding: 4px 12px 4px 0;"><strong>Email</strong></td>
            <td style="padding: 4px 0;">{{ $customer->email }}</td>
        </tr>
        @if(!empty($customer->phone))
        <tr>
            <td style="padding: 4px 12px 4px 0;"><strong>Telepon</strong></td>
            <td style="padding: 4px 0;">{{ $customer->phone }}</td>
        </tr>
        @endif
        @if(!empty($customer->plan) && !empty($customer->plan->name))
        <tr>
            <td style="padding: 4px 12px 4px 0;"><strong>Paket Saat Ini</strong></td>
            <td style="padding: 4px 0;">{{ $customer->plan->name }}</td>
        </tr>
        @endif
    </table>

    <p style="margin: 0 0 8px;"><strong>Add-on yang dipilih:</strong></p>
    <ul style="margin-top: 0; padding-left: 18px;">
        @foreach($selectedAddons as $addon)
        <li>{{ $addon->name }} - Rp {{ number_format($addon->price, 0, ',', '.') }}</li>
        @endforeach
    </ul>

    <p style="margin-top: 18px;">{{ $orderMessage }}</p>
</div>