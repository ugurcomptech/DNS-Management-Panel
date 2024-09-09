<?php
// Hata ayıklama ayarları
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$ssh_key = '';
$ssh_user = '';
$ssh_host = '';

// İşlem türünü belirleme
$action = $_POST['action'] ?? '';
$record_type = $_POST['record_type'] ?? '';
$domain = $_POST['domain'] ?? '';
$value = $_POST['value'] ?? '';
$priority = $_POST['priority'] ?? '';

// Dosya yolu
$file_path = '';

// Komutları hazırlama
switch ($action) {
    case 'list':
        $command = 'cat ' . escapeshellarg($file_path);
        $restart_command = '';  // Listeleme işleminde BIND'i yeniden başlatmaya gerek yok
        break;

    case 'add':
        if ($domain && $value) {
            if ($record_type == 'MX' && $priority === '') {
                $output = "MX kaydı için öncelik değeri gereklidir.";
                break;
            }
            if (!isValidValue($record_type, $value)) {
                $output = "Geçersiz değer: $value.";
                break;
            }
            // Kayıt türüne göre nokta eklemesi
            $value .= ($record_type == 'CNAME' || $record_type == 'MX' || $record_type == 'NS') ? '.' : '';
            // MX kaydı için öncelik ekleme
            if ($record_type == 'MX') {
                $command = "echo \"$domain IN MX $priority $value\" | sudo tee -a " . escapeshellarg($file_path);
            } else {
                $command = "echo \"$domain IN $record_type $value\" | sudo tee -a " . escapeshellarg($file_path);
            }
            $restart_command = 'sudo systemctl restart bind9';
        } else {
            $output = "Eksik bilgi: Alan ad veya değer sağlanmadı.";
            break;
        }
        break;

    case 'delete':
        if ($domain && $value) {
            if (!isValidValue($record_type, $value)) {
                $output = "Geçersiz değer: $value.";
                break;
            }
            // Python betiğini çağırma
            $python_script = 'delete_record.py'; // Python betiğinizin yolu
            $command = "python3 " . escapeshellarg($python_script) . " " . escapeshellarg($file_path) . " " . escapeshellarg($domain) . " " . escapeshellarg($value);
            $output = shell_exec($command);
            $restart_command = 'sudo systemctl restart bind9';
        } else {
            $output = "Eksik bilgi: Alan adı veya değer sağlanmadı.";
            break;
        }
        break;

    default:
        $output = "Geçersiz işlem.";
        break;
}

// Komutu çalıştırma ve sonuçları gösterme
if (isset($command)) {
    $full_command = "ssh -T -o StrictHostKeyChecking=no -i $ssh_key $ssh_user@$ssh_host \"$command\" 2>&1";
    $output = shell_exec($full_command);

    // Çıktıdan gereksiz bilgileri temizleme
    $output = preg_replace('/^Welcome to Ubuntu.*?System restart required.*$/s', '', $output);
    $output = preg_replace('/^\*\*\*.*$/m', '', $output);

    // BIND servisini yeniden başlatma (sadece ekleme ve silme işlemlerinde)
    if (isset($restart_command) && $restart_command) {
        $full_restart_command = "ssh -T -o StrictHostKeyChecking=no -i $ssh_key $ssh_user@$ssh_host \"$restart_command\" 2>&1";
        $restart_output = shell_exec($full_restart_command);
        $output .= "<h2>BIND Yeniden Başlatma Çıktısı</h2><pre>" . htmlspecialchars($restart_output) . "</pre>";
    }
}

// Geçerli değerlerin doğrulama fonksiyonu
function isValidValue($record_type, $value) {
    switch ($record_type) {
        case 'A':
            return !preg_match('/[a-zA-Z]/', $value) && filter_var($value, FILTER_VALIDATE_IP);
        case 'MX':
        case 'CNAME':
        case 'NS':
            return !preg_match('/\d/', $value);
        case 'TXT':
            return true;  // TXT kayıtları için özel bir doğrulama yapılmaz
        default:
            return true;
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DNS Yönetim</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
        }
        .card {
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #007bff;
            color: #fff;
        }
        .btn-custom {
            margin: 0.5rem 0;
        }
        .form-control {
            border-radius: 0.25rem;
        }
        .form-group label {
            font-weight: bold;
        }
        .output-container {
            margin-top: 30px;
        }
        .alert-custom {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #495057;
        }
        .hidden {
            display: none;
        }
    </style>
    <script>
        function togglePriorityField() {
            var recordType = document.getElementById('record_type').value;
            var priorityField = document.getElementById('priority_field');
            if (recordType === 'MX') {
                priorityField.classList.remove('hidden');
            } else {
                priorityField.classList.add('hidden');
            }
        }
    </script>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4 text-center">DNS Yönetim</h1>

    <!-- Kayıt Listeleme Kartı -->
    <div class="card">
        <div class="card-header">
            <h2>Kayıtları Listele</h2>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <input type="hidden" name="action" value="list">
                <button type="submit" class="btn btn-primary btn-custom">Listele</button>
            </form>
        </div>
    </div>

    <!-- Kayıt Ekleme ve Silme Kartları -->
    <div class="row">
        <!-- Kayıt Ekleme Kartı -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Kayıt Ekle</h2>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label for="record_type">Kayıt Türü:</label>
                            <select id="record_type" name="record_type" class="form-control" onchange="togglePriorityField()" required>
                                <option value="A">A</option>
                                <option value="MX">MX</option>
                                <option value="CNAME">CNAME</option>
                                <option value="NS">NS</option>
                                <option value="TXT">TXT</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="domain">Alan Adı:</label>
                            <input type="text" id="domain" name="domain" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="value">Değer:</label>
                            <input type="text" id="value" name="value" class="form-control" required>
                        </div>
                        <div id="priority_field" class="form-group hidden">
                            <label for="priority">Öncelik:</label>
                            <input type="number" id="priority" name="priority" class="form-control" min="0">
                        </div>
                        <button type="submit" class="btn btn-success btn-custom">Ekle</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Kayıt Silme Kartı -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2>Kayıt Sil</h2>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="delete">
                        <div class="form-group">
                            <label for="record_type_delete">Kayıt Türü:</label>
                            <select id="record_type_delete" name="record_type" class="form-control" required>
                                <option value="A">A</option>
                                <option value="MX">MX</option>
                                <option value="CNAME">CNAME</option>
                                <option value="NS">NS</option>
                                <option value="TXT">TXT</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="domain_delete">Alan Adı:</label>
                            <input type="text" id="domain_delete" name="domain" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="value_delete">Değer:</label>
                            <input type="text" id="value_delete" name="value" class="form-control" required>
                        </div>
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-danger btn-custom">Sil</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Çıktı Alanı -->
    <?php if (isset($output)): ?>
    <div class="output-container">
        <div class="alert alert-custom">
            <strong>Çıktı:</strong>
            <pre><?php echo htmlspecialchars($output); ?></pre>
        </div>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
