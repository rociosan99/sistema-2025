<x-guest-layout>
    <div style="width:100%; max-width:560px; margin:0 auto;">
        <div style="background:#ffffff; border:1px solid #e5e7eb; border-radius:18px; padding:32px; box-shadow:0 10px 25px rgba(0,0,0,.06);">
            <div style="margin-bottom:24px;">
                <h1 style="margin:0; font-size:28px; font-weight:700; color:#111827;">
                    Ingresar
                </h1>

                <p style="margin:10px 0 0 0; font-size:14px; color:#6b7280;">
                    Elegí cómo querés acceder al sistema.
                </p>
            </div>

            <div style="display:flex; flex-direction:column; gap:12px;">
                <a
                    href="/admin/login"
                    style="display:block; width:100%; text-align:center; text-decoration:none; background:#374151; color:#ffffff; padding:14px 16px; border-radius:12px; font-weight:700;"
                >
                    Ingresar como administrador
                </a>

                <a
                    href="/profesor/login"
                    style="display:block; width:100%; text-align:center; text-decoration:none; background:#1f2937; color:#ffffff; padding:14px 16px; border-radius:12px; font-weight:700;"
                >
                    Ingresar como profesor
                </a>

                <a
                    href="/alumno/login"
                    style="display:block; width:100%; text-align:center; text-decoration:none; background:#111827; color:#ffffff; padding:14px 16px; border-radius:12px; font-weight:700;"
                >
                    Ingresar como alumno
                </a>
            </div>

            <div style="margin-top:24px; padding-top:16px; border-top:1px solid #e5e7eb; font-size:14px; color:#6b7280;">
                ¿No tenés cuenta?
                <a
                    href="{{ route('register') }}"
                    style="font-weight:700; color:#4f46e5; text-decoration:none;"
                >
                    Registrate acá
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>