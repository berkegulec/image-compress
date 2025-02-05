# Resim Sıkıştırma Uygulaması

Bu uygulama, Laravel, Inertia.js ve React kullanılarak geliştirilmiş basit bir resim sıkıştırma aracıdır. Kullanıcıların birden fazla resmi aynı anda yükleyip sıkıştırmasına ve sıkıştırılmış dosyaları ZIP formatında indirmesine olanak tanır.

## Özellikler

- Çoklu resim yükleme desteği
- Kalite ayarı (1-100%)
- Her resim için orijinal ve sıkıştırılmış boyut gösterimi
- Toplu sıkıştırma ve ZIP olarak indirme
- Otomatik temizleme (1 saat sonra)
- Tek tek veya toplu resim silme
- Basit ve kullanıcı dostu arayüz

## Kurulum

1. Projeyi klonlayın:
```bash
git clone [repo-url]
cd image-compress
```

2. Bağımlılıkları yükleyin:
```bash
composer install
npm install
```

3. Ortam değişkenlerini ayarlayın:
```bash
cp .env.example .env
php artisan key:generate
```

4. Storage linkini oluşturun:
```bash
php artisan storage:link
```

5. Uygulamayı başlatın:
```bash
php artisan serve
npm run dev
```

## Kullanım

1. Ana sayfada "Dosya Seç" butonuna tıklayın
2. Kalite ayarını istediğiniz seviyeye getirin (1-100 arası)
3. "Resimleri Sıkıştır" butonuna tıklayın
4. İşlem tamamlandığında yeşil "Sıkıştırılmış Resimleri İndir (ZIP)" butonu görünecektir
5. Resimleri tek tek silmek için her resmin üzerindeki çarpı işaretine tıklayın
6. Tüm resimleri silmek için "Tümünü Temizle" butonunu kullanın

## Teknik Detaylar

- Laravel 11
- Inertia.js
- React
- Intervention Image (resim işleme)
- Tailwind CSS (stil)
- ZipArchive (ZIP dosyası oluşturma)
