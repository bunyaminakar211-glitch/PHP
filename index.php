<?php
// index.php - Telefon numarasından yaklaşık konum / bilgi çeken SADECE TAHMİNİ demo

// Abstract API key'inizi buraya koyun (ücretsiz alın: abstractapi.com)
$api_key = 'YOUR_ABSTRACT_API_KEY_HERE';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['phone'])) {
    $phone = trim($_POST['phone']);
    
    // Uluslararası formatta olmalı: +905xxxxxxxxx veya +90 5xx xxx xx xx
    if (!preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
        $error = "Geçersiz telefon numarası formatı. Ör: +905xxxxxxxxx";
    } else {
        // Abstract API çağrısı (cURL ile - Composer istemezseniz)
        $url = "https://phonevalidation.abstractapi.com/v1/?api_key={$api_key}&phone={$phone}";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            
            if (isset($data['valid']) && $data['valid']) {
                $result = [
                    'phone'          => $data['phone'] ?? $phone,
                    'ülke'           => $data['country']['name'] ?? 'Bilinmiyor',
                    'ülke_kodu'      => $data['country']['code'] ?? '-',
                    'şehir_tahmini'  => $data['location'] ?? 'Tam şehir bilinmiyor (operatör bazlı tahmin)',
                    'operatör'       => $data['carrier'] ?? 'Bilinmiyor',
                    'tip'            => $data['type'] ?? 'Bilinmiyor',  // mobile, landline vs.
                    'valid'          => $data['valid'] ? 'Geçerli' : 'Geçersiz',
                ];
            } else {
                $error = "Numara doğrulanamadı veya API hatası: " . ($data['error']['message'] ?? 'Bilinmeyen');
            }
        } else {
            $error = "API isteği başarısız (HTTP $http_code). API key veya limit kontrol edin.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Telefon Bilgi Sorgu (Tahmini Konum) 😈</title>
  <style>
    body { font-family: Arial; background:#111; color:#eee; text-align:center; padding:30px; }
    form { margin:30px 0; }
    input { padding:12px; width:280px; font-size:1.1rem; }
    button { padding:12px 30px; background:#c62828; color:white; border:none; cursor:pointer; }
    .result { background:#222; padding:20px; border-radius:10px; max-width:500px; margin:auto; }
    .error { color:#ff4444; font-weight:bold; }
    .warning { color:#ff9800; font-size:0.9rem; margin:20px 0; }
  </style>
</head>
<body>

  <h1>Telefon Numarasından Bilgi Sorgula 😈💯</h1>
  <p class="warning">UYARI: Bu sadece **tahmini** ülke / operatör / şehir bilgisi verir.<br>
  Gerçek GPS konumu, canlı takip vs. **imkansızdır** ve **yasadışıdır**.</p>

  <form method="POST">
    <input type="tel" name="phone" placeholder="+905xxxxxxxxx" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
    <button type="submit">Sorgula</button>
  </form>

  <?php if (isset($error)): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <?php if (isset($result)): ?>
    <div class="result">
      <h2>Sonuç:</h2>
      <p><strong>Numara:</strong> <?= htmlspecialchars($result['phone']) ?></p>
      <p><strong>Ülke:</strong> <?= htmlspecialchars($result['ülke']) ?> (<?= htmlspecialchars($result['ülke_kodu']) ?>)</p>
      <p><strong>Tahmini Bölge/Şehir:</strong> <?= htmlspecialchars($result['şehir_tahmini']) ?></p>
      <p><strong>Operatör:</strong> <?= htmlspecialchars($result['operatör']) ?></p>
      <p><strong>Tip:</strong> <?= htmlspecialchars($result['tip']) ?></p>
      <p><strong>Durum:</strong> <?= htmlspecialchars($result['valid']) ?></p>
    </div>
  <?php endif; ?>

</body>
</html>
