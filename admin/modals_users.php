<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Yeni Kullanıcı Ekle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">TC Kimlik No</label>
                        <input type="text" name="tc" class="form-control" required maxlength="11" pattern="\d{11}" title="11 Haneli TC Giriniz">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ad Soyad</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">E-Posta</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Rol</label>
                            <select name="role" class="form-select" required>
                                <option value="user">Kullanıcı (Standart)</option>
                                <option value="approver">Birim Sorumlusu</option>
                                <option value="admin">Yönetici (Admin)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Birim</label>
                            <select name="unit_id" class="form-select">
                                <option value="">Yok (Genel)</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-light border small text-muted">
                        <i class="bi bi-info-circle me-1"></i> Varsayılan şifre <strong>123456</strong> olarak atanacaktır.
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="add_user" class="btn btn-primary px-4">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Kullanıcı Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="edit_id">

                <div class="modal-body p-4">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">TC Kimlik No</label>
                        <input type="text" name="tc" id="edit_tc" class="form-control" required maxlength="11" pattern="\d{11}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ad Soyad</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">E-Posta</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Şifre Değiştir (İsteğe Bağlı)</label>
                        <input type="text" name="password" class="form-control" placeholder="Boş bırakılırsa değişmez">
                        <div class="form-text text-danger small">
                            <i class="bi bi-exclamation-circle"></i> Eğer şifre girerseniz, kullanıcı ilk girişte değiştirmeye zorlanacaktır.
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Rol</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="user">Kullanıcı</option>
                                <option value="approver">Birim Sorumlusu</option>
                                <option value="admin">Yönetici</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Birim</label>
                            <select name="unit_id" id="edit_unit" class="form-select">
                                <option value="">Yok</option>
                                <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="edit_user" class="btn btn-warning px-4">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>