# [Hexoria.net](https://hexoria.net)

## News per PHP bearbeiten

Es gibt jetzt eine Admin-Seite unter `News/admin.php`, mit der du `data/news.json` im Browser erstellen, bearbeiten und loeschen kannst.

### Lokal starten (PHP Built-in Server)

```powershell
$env:NEX_ADMIN_PASSWORD = "dein-sicheres-passwort"
php -S localhost:8000 -t .
```

Danach:
- News-Seite: `http://localhost:8000/News/`
- Admin-Seite: `http://localhost:8000/News/admin.php`

Wenn `NEX_ADMIN_PASSWORD` nicht gesetzt ist, gilt temporaer das Standardpasswort `change-me`.

