<?php
// user_footer.php

// Eğer settings dizisi tanımlı değilse hata vermemesi için varsayılan değer
$footerTitle = $settings['site_title'] ?? 'Kampüs Araç Kayıt Sistemi';
?>

<footer class="bg-white border-top mt-5 py-4">
    <div class="container">
        <div class="row align-items-center">
            
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                <div class="text-muted small">
                    &copy; <?php echo date('Y'); ?> 
                    <span class="fw-semibold text-dark"><?= htmlspecialchars($footerTitle) ?></span>
                    <span class="mx-1">&mdash;</span>
                    <span>Tüm Hakları Saklıdır.</span>
                </div>
            </div>

            <div class="col-md-6 text-center text-md-end">
                <ul class="list-inline mb-0 small">
                    <li class="list-inline-item">
                        <a href="#" class="text-decoration-none text-secondary hover-primary">Yardım Merkezi</a>
                    </li>
                    <li class="list-inline-item mx-1 text-muted">·</li>
                    <li class="list-inline-item">
                        <a href="#" class="text-decoration-none text-secondary hover-primary">Kullanım Şartları</a>
                    </li>
                    <li class="list-inline-item mx-1 text-muted">·</li>
                    <li class="list-inline-item">
                        <a href="#" class="text-decoration-none text-secondary hover-primary">Gizlilik</a>
                    </li>
                </ul>
            </div>
            
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="../assets/js/main.js"></script>

</body>
</html>