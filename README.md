# Afshad Quran Web App

A Laravel-based Quran web application for reading ayahs, exploring tafseer, morphology, and interactive dependency graphs.

## English

### Overview

Afshad Quran Web App is a Quran study project built with Laravel. It is designed to help users read ayahs, explore word-level breakdowns, review tafseer, and interact with dependency graph visualizations in a clean web interface.

### Features

- Read Quran ayahs with a structured web interface
- View tafseer entries for ayahs
- Explore morphology and word-level analysis
- Open interactive dependency graph views for supported ayahs
- Navigate between surahs and ayahs easily
- Save personal ayah notes
- Search Quran text and translations

### How To Use

1. Clone the repository:

```bash
git clone https://github.com/khanbro2/Afshad-Quran-Web-App.git
cd Afshad-Quran-Web-App
```

2. Install PHP dependencies:

```bash
composer install
```

3. Install frontend dependencies:

```bash
npm install
```

4. Create your environment file:

```bash
cp .env.example .env
```

5. Generate the application key:

```bash
php artisan key:generate
```

6. Configure your database in `.env`, then run migrations if needed:

```bash
php artisan migrate
```

7. Start the development servers:

```bash
php artisan serve
npm run dev
```

8. Open the app in your browser and start exploring surahs, ayahs, tafseer, morphology, and dependency graphs.

### Notes

- Some dependency graph data is available only for supported ayahs.
- Large dataset files are included in the project, so cloning and pushing may take more time than usual.

## اردو

### تعارف

افشاد قرآن ویب ایپ ایک Laravel پر مبنی قرآن اسٹڈی پروجیکٹ ہے۔ اس کا مقصد یہ ہے کہ صارف آیات پڑھ سکیں، لفظی تجزیہ دیکھ سکیں، تفسیر کا مطالعہ کر سکیں، اور dependency graph کو interactive انداز میں explore کر سکیں۔

### خصوصیات

- قرآن کی آیات کو ویب انٹرفیس میں پڑھنا
- آیات کی تفسیر دیکھنا
- morphology اور word-level analysis کو explore کرنا
- supported آیات کے لئے interactive dependency graph دیکھنا
- سورت اور آیت کے درمیان آسان navigation
- ذاتی نوٹس محفوظ کرنا
- قرآن کے متن اور ترجمے میں search کرنا

### استعمال کا طریقہ

1. Repository clone کریں:

```bash
git clone https://github.com/khanbro2/Afshad-Quran-Web-App.git
cd Afshad-Quran-Web-App
```

2. PHP dependencies install کریں:

```bash
composer install
```

3. Frontend dependencies install کریں:

```bash
npm install
```

4. `.env` فائل بنائیں:

```bash
cp .env.example .env
```

5. App key generate کریں:

```bash
php artisan key:generate
```

6. اپنی database settings `.env` میں set کریں، پھر ضرورت ہو تو migrations چلائیں:

```bash
php artisan migrate
```

7. Development server چلائیں:

```bash
php artisan serve
npm run dev
```

8. Browser میں ایپ کھولیں اور سورتیں، آیات، تفسیر، morphology، اور dependency graphs کو استعمال کریں۔

### اضافی نوٹس

- Dependency graph ہر آیت کے لئے موجود نہیں ہو سکتا، صرف supported آیات کے لئے دستیاب ہے۔
- پروجیکٹ میں کچھ بڑی dataset files موجود ہیں، اس لئے clone یا push میں وقت لگ سکتا ہے۔
