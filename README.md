# 🚀 Laravel 11 API Backend (with Expo Support)

ဒီ Project ဟာ **Laravel 11** ကို အသုံးပြုပြီး တည်ဆောက်ထားတဲ့ Mobile Application (Expo) များအတွက် Backend API ဖြစ်ပါတယ်။ Authentication အပိုင်းမှာ Email Login ရော Social Login ပါ ပြည့်စုံစွာ ပါဝင်ပါတယ်။

## ပါဝင်သော Features များ

* **User Authentication:** Sanctum ကို အသုံးပြုထားသော Token-based Auth။
* **Registration Flow:** Auto-username generation (`@username`), Profile image upload, OTP system။
* **Social Login:** Google Socialite အသုံးပြုထားပြီး Mobile (Expo) မှရလာသော Access Token ဖြင့် Login ဝင်ခြင်း။
* **Cloudflare R2 Storage:** ပုံများကို ပိုမိုမြန်ဆန်ပြီး ဈေးသက်သာသော Cloudflare R2 (S3 compatible) ပေါ်တင်ခြင်း။
* **Email System:** Gmail SMTP ကို အသုံးပြုပြီး OTP ပို့ပေးခြင်း။
* **Forget Password:** OTP အခြေခံသော Password Reset Flow။

---

## 🛠 တပ်ဆင်နည်း (Setup Instructions)

### ၁။ Clone the Project

```bash
git clone <your-repo-url>
cd <project-folder>

```

### ၂။ Install Dependencies

```bash
composer install

```

### ၃။ Configuration (.env)

`.env.example` ကို `.env` သို့ Copy ကူးပြီး လိုအပ်သော အချက်အလက်များ ဖြည့်ပါ။

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp_db
DB_USERNAME=root
DB_PASSWORD=

# Cloudflare R2 (Storage)
FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=your_access_key
R2_SECRET_ACCESS_KEY=your_secret_key
R2_BUCKET=your_bucket_name
R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
R2_URL=https://pub-<id>.r2.dev

# Gmail SMTP (Email)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password-16-chars
MAIL_ENCRYPTION=tls

# Socialite Google
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret

```

### ၄။ Run Migrations & Key Generate

```bash
php artisan key:generate
php artisan migrate

```

### ၅။ Run Server

```bash
php artisan serve

```

---

## 📍 API Endpoints

### Authentication

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/register` | Register new user (Multi-part form data) |
| `POST` | `/api/login` | Login with Email & Password |
| `POST` | `/api/auth/google/mobile` | Login with Google Access Token (from Expo) |
| `POST` | `/api/resend-otp` | Resend OTP to Email |
| `POST` | `/api/verify-otp` | Verify User OTP |

### Forget Password

| Method | Endpoint | Description |
| --- | --- | --- |
| `POST` | `/api/forget-password` | Request OTP for password reset |
| `POST` | `/api/verify-forget-otp` | Verify OTP and get Reset Token |
| `POST` | `/api/reset-password` | Set new password (Auth Header required) |

---

## 📝 Developer Notes

* **API Accept Header:** API ခေါ်ဆိုတိုင်း Header တွင် `Accept: application/json` အမြဲပါရပါမည်။
* **Image Upload:** Registration တွင် ပုံတင်ရန်အတွက် `form-data` ကို အသုံးပြုပါ။
* **Authentication:** Login ဝင်ပြီးနောက် ရလာသော `access_token` ကို Header တွင် `Authorization: Bearer <token>` အနေဖြင့် ထည့်သွင်းအသုံးပြုပါ။

---

## 💡 Important Commands

* `php artisan install:api` - Laravel API setup ပြုလုပ်ရန်။
* `php artisan make:mail OtpMail` - Email template အသစ်ဆောက်ရန်။

---

**Next Steps:**
GitHub သို့မဟုတ် GitLab သို့ တင်ရန်အတွက် အောက်ပါ Command များကို သုံးနိုင်ပါသည်-

```bash
git init
git add .
git commit -m "Initial Laravel 11 Backend with R2 and Social Auth"
git remote add origin <your-repo-link>
git push -u origin main

```

အဆင်ပြေပါစေ! နောက်ထပ် Features တွေ (ဥပမာ- Post တင်တာ၊ Notification ပို့တာ) လိုအပ်ရင် အချိန်မရွေး ပြန်မေးနိုင်ပါတယ်။