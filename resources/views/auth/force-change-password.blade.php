<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wajib Ganti Kata Sandi — Websinkronisasi</title>
    <style>
        *, ::after, ::before { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0b1120;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }
        .card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
        }
        h1 {
            font-size: 1.35rem;
            margin: 0 0 0.5rem 0;
            font-weight: 700;
            color: #f8fafc;
            text-align: center;
        }
        .subtitle {
            font-size: 0.875rem;
            color: #94a3b8;
            text-align: center;
            margin-bottom: 1.5rem;
            line-height: 1.4;
        }
        .alert-error {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.35rem;
            color: #cbd5e1;
        }
        input[type="password"] {
            width: 100%;
            padding: 0.65rem 0.85rem;
            background-color: #0f172a;
            border: 1px solid #475569;
            border-radius: 8px;
            color: #f8fafc;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }
        input[type="password"]:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.25);
        }
        .btn-submit {
            display: block;
            width: 100%;
            padding: 0.75rem;
            background-color: #f59e0b;
            color: #0f172a;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1rem;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #d97706;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Ganti Kata Sandi Akun</h1>
        <p class="subtitle">Demi keamanan sistem, Anda diwajibkan memperbarui kata sandi awal sebelum dapat mengakses dasbor Websinkronisasi.</p>

        @if ($errors->any())
            <div class="alert-error">
                <ul style="margin: 0; padding-left: 1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('auth.password.change.update') }}">
            @csrf

            <div class="form-group">
                <label for="new_password">Kata Sandi Baru (Min. 8 karakter)</label>
                <input type="password" id="new_password" name="new_password" required autofocus>
            </div>

            <div class="form-group">
                <label for="new_password_confirmation">Konfirmasi Kata Sandi Baru</label>
                <input type="password" id="new_password_confirmation" name="new_password_confirmation" required>
            </div>

            <button type="submit" class="btn-submit">Simpan & Lanjutkan</button>
        </form>
    </div>
</body>
</html>

