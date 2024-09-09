import sys

def delete_record(file_path, domain, ip):
    try:
        # Dosyanın var olup olmadığını kontrol etme
        with open(file_path, 'r') as file:
            lines = file.readlines()

        # Silinecek satırı oluşturma
        line_to_delete = f"{domain} IN A {ip}\n"

        # Satırı dosyadan çıkartma
        with open(file_path, 'w') as file:
            for line in lines:
                if line != line_to_delete:
                    file.write(line)

        print(f"{line_to_delete.strip()} satırı başarıyla silindi.")
    except FileNotFoundError:
        print(f"Dosya mevcut değil: {file_path}")
    except Exception as e:
        print(f"Bir hata oluştu: {e}")

if __name__ == "__main__":
    if len(sys.argv) != 4:
        print("Kullanım: delete_record.py <dosya_yolu> <alan_adı> <ip_adresi>")
        sys.exit(1)
    
    file_path = sys.argv[1]
    domain = sys.argv[2]
    ip = sys.argv[3]

    delete_record(file_path, domain, ip)
