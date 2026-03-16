
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

### iNetto - ISP Billing of Mikrotik Router (Original)
iNetto is a powerful ISP billing software built using Laravel based on
Mikrotik Router OS, designed to simplify and automate the billing processes for internet service providers.

- Version: 1.1 (iNettotik)
- Author: [CODExWP](http://codexwp.com)

#### Legacy Features
- Admin Dashboard: Modern UI built with Sneat Admin Template, offering real-time analytics and insights.
- Seller Management: Add, edit, and manage seller profiles, fund transfer, and payment history.
- User Management: Add, edit, and manage user profiles, bulk CSV export/import, bill pay, and payment history.
- Billing System: Pay bill to single/bulk users. Automated enable/disable user according to expire date and payment reminders.
- RouterOS API Integration: Seamless integration with Mikrotik RouterOS for bandwidth management and client authentication.
- Payment Gateways: Support for Stripe, bKash, and offline payment methods.
- SMS Notifications: Integration with Twilio and BulkSMSBD for sending automated messages.
- Packages: Define and manage various package plans for your users.
- Tariffs: Define and manage various tariffs and package's cost for your sellers.
- Reports: Generate detailed reports on SMS and payments of users and sellers.
- Responsive Design: Fully optimized for desktops, tablets, and mobile devices.


#### Demo URL - [http://inetto.codexwp.com](http://inetto.codexwp.com)
```
Admin Login – admin@inetto.com & 123456

Seller Login – seller1@inetto.com & 123456

User Login – user1@inetto.com & 123456
```

#### Buy License Key From Here [https://www.codexwp.com/products/](https://www.codexwp.com/products/)


-------

This document will guide you through the installation and usage of this software.

--------------------------------------------------
1. SYSTEM REQUIREMENTS
--------------------------------------------------
- PHP 8.1 or higher
- Laravel 10
- MySQL 5.7 or higher (or MariaDB equivalent)
- Composer installed on the server
- Node.js and NPM installed
- Web server (Apache, Nginx, etc.)
- Enabled PHP extensions: OpenSSL, PDO, Mbstring, Tokenizer, XML, cURL
- Mikrotik RouterOS 6+ (Enable API service, allow 8087 port in firewall, user credentials)

--------------------------------------------------
2. INSTALLATION GUIDE
--------------------------------------------------
- Upload the .Zip file to your server `public_html` folder and unzip.
- Run `composer install` to install PHP dependencies if vendor doesn't exist.
- Run `npm install` to install frontend dependencies if public/build doesn't exist.
- Build the frontend assets by running `npm run build` if public/build doesn't exist.
- Open your browser, visit (yourdomain.com/install) and follow the instructions.
- For details documentation visit here [http://inetto.codexwp.com/public/docs](http://inetto.codexwp.com/public/docs)


--------------------------------------------------
3. LICENSE
--------------------------------------------------
This software is licensed under the terms specified in the [LICENSE.txt](LICENSE) file. Please review it before use.

--------------------------------------------------
4. CREDITS
--------------------------------------------------
This project uses the following third-party libraries and services:
- [Sneat Admin Template](https://themeselection.com/) (MIT License)
- [Bootstrap](https://getbootstrap.com/) (MIT License)
- [jQuery](https://jquery.com/) (MIT License)
- [Vue.js](https://vuejs.org/) (MIT License)
- [SweetAlert](https://sweetalert.js.org/) (MIT License)
- [Mikrotik RouterOS API](https://github.com/BenMenking/routeros-api) (Check specific license)
- [Stripe API](https://stripe.com/)
- [bKash API](https://developer.bkash.com/)
- [BulkSMSBD](https://www.bulksmsbd.com/)
- [Twilio API](https://www.twilio.com/)


--------------------------------------------------
5. CONTACT
--------------------------------------------------
If you need support or have queries, please contact:
- Email: info@codexwp.com
- Contact Us: [support.codexwp.com](https://codexwp.com/contact-us)

Thank you for using iNetto!
