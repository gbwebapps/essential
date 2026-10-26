# Prove delle preferenze temporanee

Le prove usano esclusivamente `essential_test`. Non modificare `.env` o `Database.php`.
Servono le credenziali già configurate in `.env.playwright`.

Dal root del progetto, aprire tre terminali PowerShell.

## 1. Backend isolato

```powershell
[Environment]::SetEnvironmentVariable('database.defaultGroup', 'tests', 'Process')
[Environment]::SetEnvironmentVariable('app.baseURL', 'https://127.0.0.1:8770/', 'Process')
[Environment]::SetEnvironmentVariable('cookie.path', '/', 'Process')
[Environment]::SetEnvironmentVariable('cookie.secure', 'true', 'Process')
[Environment]::SetEnvironmentVariable('cache.handler', 'dummy', 'Process')
$env:CI_ENVIRONMENT = 'e2e'
php -S 127.0.0.1:8769 -t public e2e/support/interface-router.php
```

## 2. HTTPS locale

Usare un certificato locale; adattare i percorsi alla propria installazione.

```powershell
$env:INTERFACE_TLS_CERT = 'D:/Laragon/etc/ssl/laragon.crt'
$env:INTERFACE_TLS_KEY = 'D:/Laragon/etc/ssl/laragon.key'
node e2e/support/interface-https.cjs
```

## 3. Test browser

```powershell
[Environment]::SetEnvironmentVariable('database.defaultGroup', 'tests', 'Process')
$env:CI_ENVIRONMENT = 'e2e'
npx playwright test -c playwright.interface.config.js
```

Il router rifiuta gruppi database diversi da `tests`; il setup verifica anche il nome reale `essential_test`.
Il test di General ripristina lingua e tema iniziali in `finally`.
Le schermate sono in `output/interface-preferences`. Fermare entrambi i server con Ctrl+C.
