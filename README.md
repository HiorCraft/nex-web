# [Hexoria.net](https://hexoria.net)

## News und Mods per PHP bearbeiten

Es gibt jetzt getrennte Admin-Seiten fuer `News` und `Mods`, die beide eine Anmeldung ueber Session erfordern.

- Login: `Admin/login.php`
- Admin Start: `Admin/index.php`
- News Admin: `News/admin.php`
- Mods Admin: `Mods/admin.php`

### Lokal starten (PHP Built-in Server)

```powershell
$env:NEX_ADMIN_PASSWORD = "dein-sicheres-passwort"
php -S localhost:8000 -t .
```

Danach:
- News-Seite: `http://localhost:8000/News/`
- Mods-Seite: `http://localhost:8000/Mods/`
- Login: `http://localhost:8000/Admin/login.php`
- Admin Start: `http://localhost:8000/Admin/`

Wenn `NEX_ADMIN_PASSWORD` nicht gesetzt ist, gilt temporaer das Standardpasswort `change-me`.
