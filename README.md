# eGallery API

eGallery je REST API aplikacija namenjena umetnicima koji žele da kreiraju digitalnu galeriju i predstave svoje radove publici. Sistem omogućava upravljanje korisničkim i umetničkim profilima, kategorijama i umetničkim delima, pretragu i filtriranje galerije, upload slika i preuzimanje podataka sa javnih muzejskih API-ja.

Projekat je razvijen u PHP-u korišćenjem Laravel 12 radnog okvira, MySQL baze podataka, Eloquent ORM-a, Laravel Sanctum autentifikacije i OpenAPI/Swagger dokumentacije.

## Funkcionalnosti

- registracija posetioca ili umetnika i prijavljivanje pomoću Bearer tokena;
- uloge `admin`, `artist` i `visitor`;
- pregled i ažuriranje korisničkog profila;
- kreiranje i ažuriranje dodatnih podataka umetničkog profila;
- javni pregled svih umetnika i pojedinačnog umetnika;
- javni pregled kategorija, uz administratorsko kreiranje, ažuriranje i brisanje;
- javni pregled svih umetničkih dela i pojedinačnog dela;
- pretraga, paginacija, filtriranje po umetniku i kategoriji i sortiranje umetničkih dela;
- pregled umetničkih dela određene kategorije ili umetnika preko ugnježdenih ruta;
- kreiranje umetničkog dela od strane umetnika;
- ažuriranje i brisanje dela samo od strane umetnika koji je njegov vlasnik;
- slanje slike kao lokalnog fajla ili zadavanje udaljenog URL-a;
- preuzimanje umetničkih dela sa javnih API-ja Art Institute of Chicago i Cleveland Museum of Art;
- interaktivna Swagger dokumentacija celog API-ja;
- seederi sa poznatim umetnicima, stvarnim umetničkim delima i demonstracionim podacima.

## Sistemski zahtevi

Za lokalno pokretanje potrebno je instalirati:

- PHP 8.2 ili noviji sa potrebnim Laravel ekstenzijama i `pdo_mysql` ekstenzijom;
- Composer;
- MySQL ili kompatibilnu MariaDB bazu;
- Node.js i npm;
- Git.

## Preuzimanje projekta

Klonirajte repozitorijum i pređite u direktorijum projekta:

```bash
git clone repo_url
cd egallery
```

Ako projekat već postoji na lokalnoj mašini, najnovije izmene mogu se preuzeti sledećim komandama:

```bash
git checkout main
git pull origin main
composer install
npm install
php artisan migrate
php artisan l5-swagger:generate
npm run build
```

Pre pokretanja `git pull` komande potrebno je sačuvati ili commitovati sopstvene lokalne izmene kako ne bi došlo do konflikta.

## Instalacija projekta

Instalirajte PHP i JavaScript zavisnosti:

```bash
composer install
npm install
```

Napravite lokalnu `.env` datoteku na osnovu priloženog primera. Na Windows sistemu može se koristiti:

```powershell
Copy-Item .env.example .env
```

Na Linux i macOS sistemima koristi se:

```bash
cp .env.example .env
```

Zatim generišite aplikacioni ključ:

```bash
php artisan key:generate
```

## Podešavanje baze podataka

U MySQL-u kreirajte praznu bazu podataka:

```sql
CREATE DATABASE egallery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

U `.env` datoteci podesite konekciju prema lokalnom MySQL okruženju:

```env
APP_NAME=eGallery
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=egallery
DB_USERNAME=root
DB_PASSWORD=
```

Pokrenite migracije i seedere:

```bash
php artisan migrate --seed
```

Ako je potrebno potpuno obrisati razvojnu bazu i ponovo kreirati sve tabele i demonstracione podatke, može se koristiti sledeća komanda. Ona briše sve postojeće podatke iz konfigurisane baze, pa je namenjena isključivo lokalnom razvojnom okruženju:

```bash
php artisan migrate:fresh --seed
```

## Podešavanje slika i Swagger dokumentacije

Kreirajte simboličku vezu preko koje će lokalno uploadovane slike biti javno dostupne:

```bash
php artisan storage:link
```

Generišite aktuelnu OpenAPI dokumentaciju:

```bash
php artisan l5-swagger:generate
```

## Pokretanje aplikacije

API server se može pokrenuti komandom:

```bash
php artisan serve
```

Aplikacija će podrazumevano biti dostupna na adresi:

```text
http://127.0.0.1:8000
```

Osnovna adresa API-ja je:

```text
http://127.0.0.1:8000/api
```

Swagger dokumentacija dostupna je na adresi:

```text
http://127.0.0.1:8000/api/documentation
```

Ako se radi i sa Vite resursima, u drugom terminalu može se pokrenuti:

```bash
npm run dev
```

Laravel server, Vite razvojni server i queue worker mogu se zajedno pokrenuti Composer skriptom:

```bash
composer run dev
```

## Demonstracioni korisnici

Nakon izvršavanja seedera dostupni su sledeći nalozi:

| Uloga         | E-mail                           | Lozinka    |
| ------------- | -------------------------------- | ---------- |
| Administrator | `admin@egallery.test`            | `password` |
| Umetnik       | `vincent.van.gogh@egallery.test` | `password` |
| Umetnik       | `claude.monet@egallery.test`     | `password` |
| Umetnik       | `demo.artist.one@egallery.test`  | `password` |

Ovi kredencijali namenjeni su samo lokalnom razvoju i demonstraciji aplikacije.

## Autentifikacija API zahteva

Korisnik se prijavljuje slanjem `POST /api/login` zahteva. Dobijeni token se kod zaštićenih ruta šalje u HTTP zaglavlju:

```http
Authorization: Bearer <token>
Accept: application/json
```

Kod slanja slike kao fajla zahtev za kreiranje umetničkog dela mora biti tipa `multipart/form-data`. Kada se koristi udaljena slika, umesto fajla šalje se URL predviđenim poljem. Zahtev mora sadržati samo jedan od ova dva izvora slike.

## Glavne grupe API ruta

- `/api/register`, `/api/login` i `/api/logout` služe za autentifikaciju;
- `/api/user` omogućava pregled i ažuriranje profila prijavljenog korisnika;
- `/api/artists` omogućava javni pregled umetnika;
- `/api/categories` omogućava pregled kategorija i administratorsko upravljanje;
- `/api/artworks` omogućava pregled galerije i upravljanje umetničkim delima;
- `/api/categories/{id}/artworks` vraća dela određene kategorije;
- `/api/artists/{id}/artworks` vraća dela određenog umetnika;
- `/api/external/artworks/art-institute` i `/api/external/artworks/cleveland` preuzimaju podatke sa javnih API-ja.

Potpuni parametri zahteva, filteri, pravila validacije i primeri odgovora nalaze se u Swagger dokumentaciji.

## Testiranje

Kompletan skup automatizovanih testova pokreće se komandom:

```bash
php artisan test
```

Alternativno se može koristiti Composer skripta:

```bash
composer test
```

## Korisne razvojne komande

Nakon izmene ruta, konfiguracije ili Swagger anotacija mogu se koristiti:

```bash
php artisan optimize:clear
php artisan l5-swagger:generate
composer dump-autoload
```

Za pregled svih registrovanih ruta koristi se:

```bash
php artisan route:list
```
