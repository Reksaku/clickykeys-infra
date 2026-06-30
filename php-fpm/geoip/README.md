# GeoIP database

`track.php` resolves the visitor's **country** from their IP using a MaxMind-format
`.mmdb` database, read by the native `maxminddb` PHP extension. The lookup happens
**in memory, before the IP is anonymized** — the full IP is never written to the DB,
only the 2-letter ISO country code.

## Which database

We use **DB-IP IP-to-Country Lite** (MMDB), which is freely downloadable without an
account and licensed CC-BY-4.0. It is refreshed monthly, so it is **not committed** to
git — it is downloaded at image-build time (see CI) or manually for local builds.

Expected file: `geoip/dbip-country-lite.mmdb`
Override the path at runtime with the `GEOIP_DB_PATH` env var.

## Download manually (local build)

```sh
MONTH=$(date +%Y-%m)
curl -fsSL "https://download.db-ip.com/free/dbip-country-lite-${MONTH}.mmdb.gz" \
  | gunzip > dbip-country-lite.mmdb
```

## Notes

- If the file is missing or unreadable, tracking still works — `country` is simply
  stored as `NULL`. Geo is best-effort and never breaks page tracking.
- The Lite **Country** DB has no region/city data. To also capture region, switch to
  `dbip-city-lite.mmdb` (larger) and read `subdivisions[0].iso_code` in `track.php`.
- Alternative source: MaxMind **GeoLite2-Country** (same format, requires a free
  license key). Just drop it in as `dbip-country-lite.mmdb` or point `GEOIP_DB_PATH` at it.
