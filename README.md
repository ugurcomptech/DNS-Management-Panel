# DNS YÖNETİM PANELİ

Bu proje, bir DNS sunucusunda DNS kayıtlarını yönetmek için bir web arayüzü sağlar. Kullanıcılar, `A`, `MX`, `CNAME`, `NS`, ve `TXT` kayıtlarını listeleyebilir, ekleyebilir ve silebilir. Bu sistem, kullanıcıların DNS kayıtlarını kolayca yönetmelerine olanak tanır.



## Özellikler

- DNS kayıtlarını listeleme
- DNS kayıtlarını ekleme ve silme
- `MX` kayıtları için öncelik değeri ekleme
- Otomatik olarak kayıt değerlerinin sonuna nokta eklenir (CNAME, MX, NS)
- Geçersiz değerler için kullanıcı uyarıları

## Gereksinimler

- PHP 7.0 veya üzeri
- Apache veya Nginx web sunucusu
- BIND DNS sunucusu (veya benzer bir DNS sunucu yazılımı)
- SSH erişimi


## 1. Proje Dosyalarını İndirin

```
git clone https://github.com/ugurcomptech/DNS-Management-Panel.git
cd DNS-Management-Panel
```

## 2. Yapılandırmaları Düzenleyin

index.php dosyasında SSH anahtarınızı ve sunucu bilgilerinizi doğru şekilde ayarlayın:

```
$ssh_key = 'path_to_your_ssh_key.pem';
$ssh_user = 'your_ssh_username';
$ssh_host = 'your_ssh_host';
```
Ayrıca, DNS kayıt dosyanızın yolunu index.php dosyasında ayarlayın:
```
$file_path = '/etc/bind/zones/your_domain.db';
```



## Önemli

Bu proje hala geliştirme aşamasındadır. Güncellemeleri paylaşıyor olacağım.

