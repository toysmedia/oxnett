
# iNettotik — Kenyan ISP Billing System

**iNettotik** is a production-ready, large-scale ISP billing system for Kenyan WISPs (Wireless Internet Service Providers) built on Laravel 10. It integrates FreeRADIUS 3.x, MikroTik RouterOS v6.49+/v7.x, M-Pesa Daraja API, and Africa's Talking SMS.

> Built on top of the original iNetto codebase with full ISP billing extensions.

## ✨ ISP Billing Features

| Feature | Details |
|---------|---------|
| **Multi-Router Support** | Unlimited routers, one-click MikroTik `.rsc` script generation |
| **FreeRADIUS Integration** | Same MySQL DB — radcheck, radreply, radacct, nas tables |
| **PPPoE + Hotspot** | Simultaneous support, both via RADIUS auth |
| **M-Pesa Daraja** | STK Push + C2B Paybill, auto-provisioning after payment |
| **Hotspot Login Pages** | Dark-themed, M-Pesa reference login, downloadable as ZIP |
| **Africa's Talking SMS** | Voucher delivery after payment |
| **Subscriber Management** | 10,000+ users, bulk actions, expiry management |
| **Live Sessions** | Real-time radacct monitoring, admin disconnect |
| **Reseller Module** | Commission-based reseller management |
| **Customer Portal** | Self-service package purchase and renewal |
| **Audit Logging** | Kenya Data Protection Act ready |

## 🚀 Quick Start

```bash
# Clone and install
git clone https://github.com/toysmedia/iNettotik.git
cd iNettotik
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate

# Configure database and M-Pesa in .env
php artisan migrate
php artisan storage:link

# Setup cron for user expiry
* * * * * cd /var/www/iNettotik && php artisan schedule:run >> /dev/null 2>&1
```

## 📖 Documentation

- [Installation Guide](docs/installation.md) — Full Ubuntu 22.04 setup
- [FreeRADIUS SQL Config](docs/freeradius-sql-config.md) — Connect FreeRADIUS to Laravel DB

## 🔑 Key URLs

| URL | Description |
|-----|-------------|
| `/admin/isp/dashboard` | ISP admin dashboard |
| `/admin/isp/routers` | Router management + script generation |
| `/admin/isp/subscribers` | Subscriber management |
| `/admin/isp/sessions` | Live RADIUS sessions |
| `/admin/isp/payments` | M-Pesa payment history |
| `/buy` | Public package purchase page |
| `/customer/dashboard` | Customer self-service portal |
| `/api/mpesa/stk-callback` | Safaricom STK Push webhook |
| `/api/mpesa/c2b-confirmation` | Safaricom C2B webhook |

## 🌐 M-Pesa Flow

1. Customer visits `/buy` → selects package → enters phone
2. STK Push sent to phone → customer enters M-Pesa PIN
3. Safaricom calls `/api/mpesa/stk-callback` webhook
4. System auto-provisions FreeRADIUS entry (hotspot voucher or PPPoE extension)
5. SMS sent with voucher code (hotspot) or confirmation (PPPoE)

## 📡 MikroTik Setup

1. Add router in **Admin → ISP → Routers**
2. Click **"Generate Script"** → copy/download the `.rsc` file
3. Paste into MikroTik Terminal (Winbox or SSH)
4. Click **"Download Hotspot Files"** → upload ZIP to `/hotspot` folder in MikroTik

---

