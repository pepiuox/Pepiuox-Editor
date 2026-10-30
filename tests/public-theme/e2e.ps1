# Verificación HTTP de la personalización visual pública.
# Autentica con una sesión PHP real creada por storage/_ppf_prep.php.
# Uso: ./tests/public-theme/e2e.ps1

$ErrorActionPreference = 'Stop'
$root  = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$base  = 'http://127.0.0.1:8899'
$pass  = 0
$fail  = 0

function Check([string]$name, [bool]$ok, [string]$detail = '') {
    if ($ok) { $script:pass++; Write-Host "  PASA  $name" }
    else { $script:fail++; Write-Host "  FALLA $name -> $detail" }
}

$prep = & php "$PSScriptRoot\support.php" prep | ConvertFrom-Json
$sid     = $prep.session_id
$member  = $prep.member_session
$csrf    = $prep.csrf
$admin   = @("-b", "PEP_SESSION=$sid")
$memberC = @("-b", "PEP_SESSION=$member")
$media   = $prep.media_dir
$logos   = "$root\uploads\settings\logos"

Write-Host "== A. Subida de logo (PNG valido) =="
$code = curl.exe -s -o NUL -w "%{http_code}" @admin `
    -F "slot=logo_header" -F "asset=@$media/logo.png" -F "_csrf_token=$csrf" `
    "$base/admin/settings/brand/asset"
Check 'responde 302 (redirect con flash)' ($code -eq '302') "http=$code"
$stored = (& php "$PSScriptRoot\support.php" get public logo_header).Trim()
Check 'la clave public_logo_header se guarda' ($stored -like 'settings/logos/logo_header-*') "valor=$stored"
$abs = "$root\uploads\" + ($stored -replace '/', '\')
Check 'el archivo existe en uploads/settings/logos' (Test-Path $abs) $abs
Check 'nombre generado por el servidor, no el del usuario' ($abs -notlike '*logo.png') $abs

Write-Host "`n== B. Subida rechazada (PNG falsificado) =="
$before = $stored
$code = curl.exe -s -o NUL -w "%{http_code}" @admin `
    -F "slot=logo_tablet" -F "asset=@$media/fake.png" -F "_csrf_token=$csrf" `
    "$base/admin/settings/brand/asset"
$after = (& php "$PSScriptRoot\support.php" get public logo_tablet).Trim()
Check 'no se registra la clave' ($after -eq '') "valor=$after"
Check 'no queda archivo nuevo' ((Get-ChildItem $logos -Filter 'logo_tablet-*' -ErrorAction SilentlyContinue | Measure-Object).Count -eq 0)

Write-Host "`n== C. Subida de SVG con codigo (saneado) =="
$code = curl.exe -s -o NUL -w "%{http_code}" @admin `
    -F "slot=logo_footer" -F "asset=@$media/logo.svg" -F "_csrf_token=$csrf" `
    "$base/admin/settings/brand/asset"
$svgSetting = (& php "$PSScriptRoot\support.php" get public logo_footer).Trim()
Check 'el SVG se guarda' ($svgSetting -like 'settings/logos/logo_footer-*.svg') "valor=$svgSetting"
$svgAbs = "$root\uploads\" + ($svgSetting -replace '/', '\')
if (Test-Path $svgAbs) {
    $content = Get-Content $svgAbs -Raw
    Check 'SVG sin <script>' (-not ($content -match '<script')) 'quedo el script'
    Check 'SVG sin onmouseover' (-not ($content -match 'onmouseover')) 'quedo el handler'
    Check 'SVG conserva el rect legible' ($content -match '<rect')
} else { Check 'archivo SVG presente' $false $svgAbs }

Write-Host "`n== D. Favicon =="
$code = curl.exe -s -o NUL -w "%{http_code}" @admin `
    -F "slot=favicon_32" -F "asset=@$media/icon32.png" -F "_csrf_token=$csrf" `
    "$base/admin/settings/brand/asset"
$fav = (& php "$PSScriptRoot\support.php" get public favicon_32).Trim()
Check 'favicon_32 guardado' ($fav -like 'settings/favicons/favicon_32-*.png') "valor=$fav"
$html = (curl.exe -s "$base/") -join "`n"
Check 'el <head> publico emite favicon 32x32' ($html -match 'rel="icon"[^>]*sizes="32x32"')
Check 'el <head> publico emite el manifiesto' ($html -match 'rel="manifest"')
Check 'el body publico lleva la clase public-site' ($html -match '<body class="public-site">')
$manifest = (curl.exe -s "$base/site.webmanifest") -join "`n"
Check 'el manifiesto lista el icono' ($manifest -match 'favicon_32-')

Write-Host "`n== E. Logo en el HTML publico =="
$html = (curl.exe -s "$base/") -join "`n"
Check 'usa <picture> para el logo del header' ($html -match '<picture class="brand-picture">')
Check 'el <img> lleva alt' ($html -match 'class="brand-logo" src="[^"]+" alt="')
Check 'carga la hoja dinamica con version' ($html -match 'public-theme\.css\?v=[0-9a-f]{16}')

Write-Host "`n== F. CSS dinamico refleja la configuracion =="
$css = (curl.exe -s "$base/public-theme.css") -join "`n"
Check 'CSS contiene --public-primary' ($css -match '--public-primary:\s*#00d4aa')
Check 'CSS contiene --public-footer-bg' ($css -match '--public-footer-bg:')

Write-Host "`n== G. Validacion de color invalido =="
$code = curl.exe -s -o NUL -w "%{http_code}" @admin `
    -d "theme=personalizado" -d "colors[color_primary]=no-es-un-color" -d "_csrf_token=$csrf" `
    "$base/admin/settings/brand"
$primary = (& php "$PSScriptRoot\support.php" get public color_primary).Trim()
Check 'el color invalido no se persiste' ($primary -eq '#00d4aa') "valor=$primary"

Write-Host "`n== H. CSRF y permisos =="
$code = curl.exe -s -o NUL -w "%{http_code}" @admin `
    -d "theme=oscuro" `
    "$base/admin/settings/brand"
Check 'POST sin token CSRF -> 403' ($code -eq '403') "http=$code"

$code = curl.exe -s -o NUL -w "%{http_code}" @memberC `
    -F "slot=logo_menu" -F "asset=@$media/logo.png" -F "_csrf_token=$csrf" `
    "$base/admin/settings/brand/asset"
Check 'rol sin settings.manage -> 403' ($code -eq '403') "http=$code"
$menu = (& php "$PSScriptRoot\support.php" get public logo_menu).Trim()
Check 'el rol sin permiso no escribe' ($menu -eq '') "valor=$menu"

Write-Host "`n== I. Eliminacion =="
$code = curl.exe -s -o NUL -w "%{http_code}" @admin `
    -d "slot=logo_footer" -d "_csrf_token=$csrf" `
    "$base/admin/settings/brand/asset/delete"
$afterDel = (& php "$PSScriptRoot\support.php" get public logo_footer).Trim()
Check 'la clave queda vacia' ($afterDel -eq '') "valor=$afterDel"
Check 'el archivo se borra del disco' (-not (Test-Path $svgAbs)) $svgAbs
$html = (curl.exe -s "$base/") -join "`n"
Check 'el logo de footer deja de ser el archivo borrado' (-not ($html -match 'logo_footer-'))

Write-Host "`n== J. Restablecer colores =="
$code = curl.exe -s -o NUL -w "%{http_code}" @admin `
    -d "theme=claro" -d "_csrf_token=$csrf" `
    "$base/admin/settings/brand/reset"
$bg = (& php "$PSScriptRoot\support.php" get public color_background).Trim()
Check 'reset aplica la paleta del tema claro' ($bg -eq '#ffffff') "valor=$bg"
$html = (curl.exe -s "$base/") -join "`n"
Check 'data-bs-theme pasa a light' ($html -match 'data-bs-theme="light"')
$code = curl.exe -s -o NUL -w "%{http_code}" @admin `
    -d "theme=oscuro" -d "_csrf_token=$csrf" `
    "$base/admin/settings/brand/reset"
$bg = (& php "$PSScriptRoot\support.php" get public color_background).Trim()
Check 'vuelve a oscuro' ($bg -eq '#0d1117') "valor=$bg"

Write-Host "`n== L. CSS personalizado: se aplica y queda acotado =="
curl.exe -s -o NUL @admin `
    -d "theme=oscuro" -d "custom_css_enabled=1" `
    -d "custom_css=.ppf-prueba { outline: 3px solid red; }" -d "_csrf_token=$csrf" `
    "$base/admin/settings/brand" | Out-Null
$css = (curl.exe -s "$base/public-theme.css") -join "`n"
Check 'el CSS personalizado llega al sitio publico' ($css -match 'ppf-prueba')
Check 'queda prefijado con body.public-site' ($css -match 'body\.public-site \.ppf-prueba')
Check 'el panel no incluye el CSS publico' (-not ($css -match 'maker\.css'))
# Desactivado: desaparece
curl.exe -s -o NUL @admin `
    -d "theme=oscuro" -d "custom_css_enabled=0" `
    -d "custom_css=.ppf-prueba { outline: 3px solid red; }" -d "_csrf_token=$csrf" `
    "$base/admin/settings/brand" | Out-Null
$css = (curl.exe -s "$base/public-theme.css") -join "`n"
Check 'desactivado no se emite' (-not ($css -match 'ppf-prueba'))

Write-Host "`n== M. Aislamiento del panel (requisito 9) =="
$panelCode = curl.exe -s -o "$env:TEMP\ppf_panel.html" -w "%{http_code}" @admin "$base/admin/settings"
$panel = (Get-Content "$env:TEMP\ppf_panel.html" -Raw) -join "`n"
Check 'el panel responde 200 con sesion' ($panelCode -eq '200') "http=$panelCode"
Check 'el panel NO carga public-theme.css' (-not ($panel -match 'public-theme\.css'))
Check 'el panel NO define variables --public-*' (-not ($panel -match '--public-[a-z]+:'))
Check 'el panel conserva su propia hoja maker.css' ($panel -match 'maker\.css')
Check 'el panel no lleva la clase public-site en <body>' (-not ($panel -match '<body class="public-site"'))
Check 'el panel muestra las 11 pestanas' (([regex]::Matches($panel, 'data-tab="')).Count -ge 22) 'cuenta inesperada'
Remove-Item "$env:TEMP\ppf_panel.html" -ErrorAction SilentlyContinue

Write-Host "`n== N. Nombre, descripcion e idioma del sitio (Configuracion -> General) =="
$origName = (& php "$PSScriptRoot\support.php" get general site_name).Trim()
$origDesc = (& php "$PSScriptRoot\support.php" get general site_description).Trim()

# 1. Guardar desde el formulario real de Configuracion
$code = curl.exe -s -o NUL -w "%{http_code}" @admin `
    --data-urlencode "settings[general][site_name]=Taller Prueba & Cia" `
    -d "_csrf_token=$csrf" "$base/admin/settings/update"
Check 'guardar el nombre responde 302' ($code -eq '302') "http=$code"
$saved = (& php "$PSScriptRoot\support.php" get general site_name).Trim()
Check 'el nombre queda en la base de datos' ($saved -eq 'Taller Prueba & Cia') "valor=$saved"

# 2. Y se ve en el sitio publico
# La marca del navbar lleva aria-label siempre (con logo o con texto), asi que
# se comprueba ahi: el bloque A dejo un logo configurado y entonces no hay
# wordmark de texto.
$html = (curl.exe -s "$base/") -join "`n"
Check 'el nombre aparece en la cabecera (navbar)' ($html -match 'aria-label="Taller Prueba &amp; Cia · Inicio"') 'no aparece en la cabecera'
Check 'el <title> lleva el nombre del sitio' ($html -match '<title>[^<]*Taller Prueba &amp; Cia</title>') 'titulo sin el nombre'
Check 'og:site_name lleva el nombre' ($html -match 'og:site_name" content="Taller Prueba &amp; Cia"')
Check 'el & se escapa una sola vez' (-not ($html -match '&amp;amp;')) 'doble escape detectado'

# 3. Descripcion: la cadena es pagina -> SEO por defecto -> descripcion del sitio
$origSeoDesc = (& php "$PSScriptRoot\support.php" get seo default_meta_description).Trim()
curl.exe -s -o NUL @admin --data-urlencode "settings[general][site_description]=Descripcion de prueba del sitio" `
    --data-urlencode "settings[seo][default_meta_description]=" `
    -d "_csrf_token=$csrf" "$base/admin/settings/update" | Out-Null
$html = (curl.exe -s "$base/") -join "`n"
Check 'sin SEO por defecto, manda la descripcion del sitio' ($html -match '<meta name="description" content="Descripcion de prueba del sitio"') 'meta description no actualizada'

curl.exe -s -o NUL @admin --data-urlencode "settings[seo][default_meta_description]=Meta de prueba SEO" `
    -d "_csrf_token=$csrf" "$base/admin/settings/update" | Out-Null
$html = (curl.exe -s "$base/") -join "`n"
Check 'con SEO por defecto, manda la de SEO' ($html -match '<meta name="description" content="Meta de prueba SEO"') 'la SEO por defecto no gana'

# 4. Restaurar
curl.exe -s -o NUL @admin --data-urlencode "settings[general][site_name]=$origName" `
    --data-urlencode "settings[general][site_description]=$origDesc" `
    --data-urlencode "settings[seo][default_meta_description]=$origSeoDesc" `
    -d "_csrf_token=$csrf" "$base/admin/settings/update" | Out-Null
$restored = (& php "$PSScriptRoot\support.php" get general site_name).Trim()
Check 'nombre restaurado al terminar' ($restored -eq $origName) "valor=$restored"
$restoredSeo = (& php "$PSScriptRoot\support.php" get seo default_meta_description).Trim()
Check 'SEO por defecto restaurado' ($restoredSeo -eq $origSeoDesc) "valor=$restoredSeo"

Write-Host "`n== O. Los diez huecos: unos se pintan y otros se ocultan =="
# logo_menu -> cabecera del menu desplegado en movil
curl.exe -s -o NUL @admin -F "slot=logo_menu" -F "asset=@$media/logo.png" -F "_csrf_token=$csrf" `
    "$base/admin/settings/brand/asset" | Out-Null
$html = (curl.exe -s "$base/") -join "`n"
Check 'logo_menu se pinta en la cabecera del menu' ($html -match 'class="menu-brand d-lg-none"') 'no aparece el bloque del menu'
Check 'usa el archivo subido' ($html -match 'brand-logo--menu" src="[^"]*logo_menu-')

# logo_multinivel -> cabecera de cada desplegable, via CSS
curl.exe -s -o NUL @admin -F "slot=logo_multinivel" -F "asset=@$media/logo.png" -F "_csrf_token=$csrf" `
    "$base/admin/settings/brand/asset" | Out-Null
$css = (curl.exe -s "$base/public-theme.css") -join "`n"
Check 'logo_multinivel llega a los desplegables' ($css -match 'ul\.dropdown-menu::before') 'falta la regla de los submenus'
Check 'la regla apunta al archivo configurado' ($css -match 'logo_multinivel-[0-9a-f]+\.png') 'la regla no referencia el archivo'

# logo_sidebar -> sin componente: no debe ofrecerse en la interfaz
curl.exe -s -o "$env:TEMP\ppf_panel_2.html" @admin "$base/admin/settings" | Out-Null
$panel = Get-Content "$env:TEMP\ppf_panel_2.html" -Raw
Check 'la interfaz NO ofrece el hueco de la barra lateral' (-not ($panel -match 'logo_sidebar')) 'sigue apareciendo logo_sidebar'
Check 'la interfaz ofrece el hueco del menu' ($panel -match 'name="slot" value="logo_menu"')
Check 'la interfaz ofrece el hueco multinivel' ($panel -match 'name="slot" value="logo_multinivel"')
# Cada hueco aparece dos veces en el HTML (formulario de subida y de borrado),
# asi que se cuentan los nombres distintos, no las apariciones.
$slotsPanel = [regex]::Matches($panel, 'value="(logo_[a-z_]+)"') |
    ForEach-Object { $_.Groups[1].Value } | Sort-Object -Unique
Check 'la interfaz ofrece 9 huecos de logo' ($slotsPanel.Count -eq 9) ("distintos=" + $slotsPanel.Count)
Remove-Item "$env:TEMP\ppf_panel_2.html" -ErrorAction SilentlyContinue

Write-Host "`n== K. Limpieza (la prueba no debe dejar rastro en el sitio) =="
foreach ($slot in 'logo_header', 'favicon_32', 'logo_tablet', 'logo_menu', 'logo_multinivel') {
    curl.exe -s -o NUL @admin -d "slot=$slot" -d "_csrf_token=$csrf" `
        "$base/admin/settings/brand/asset/delete" | Out-Null
    $left = (& php "$PSScriptRoot\support.php" get public $slot).Trim()
    Check "clave $slot vacía al terminar" ($left -eq '') "valor=$left"
}
$orphans = @(Get-ChildItem "$root\uploads\settings" -Recurse -File -ErrorAction SilentlyContinue)
Check 'no quedan archivos de prueba en uploads/settings' ($orphans.Count -eq 0) ($orphans.Name -join ', ')
# El CSS de prueba no puede quedarse guardado en el sitio del usuario.
curl.exe -s -o NUL @admin -d "theme=oscuro" -d "custom_css=" -d "custom_css_enabled=0" `
    -d "_csrf_token=$csrf" "$base/admin/settings/brand" | Out-Null
$leftCss = (& php "$PSScriptRoot\support.php" get public custom_css).Trim()
Check 'CSS personalizado vacío al terminar' ($leftCss -eq '') "valor=$leftCss"

Write-Host "`n─────────────────────────────"
Write-Host "Resultado E2E: $pass pasan, $fail fallan"
if ($fail -ne 0) { exit 1 }



