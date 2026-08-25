<?php
$pageTitle = 'Crear Usuario';
$roles = $roles ?? [];
?>

<div class="d-flex justify-between align-center mb-3 flex-wrap gap-2">
  <h2 class="mb-0"><i class="bi bi-person-plus text-primary"></i> Crear nuevo usuario</h2>
  <a class="btn btn-ghost" href="<?= url('admin/users') ?>">
    <i class="bi bi-arrow-left"></i> Volver al listado
  </a>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header">
        <h3><i class="bi bi-person"></i> Información del usuario</h3>
      </div>
      <div class="card-body">
        <form method="post" action="<?= url('admin/users') ?>" id="userForm">
          <?= csrf_field() ?>
          
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-at"></i> Nombre de usuario <span class="text-danger">*</span>
              </label>
              <input type="text" name="username" class="form-control" 
                     value="<?= old('username') ?>" required 
                     minlength="3" maxlength="50" pattern="[a-zA-Z0-9_\-\.]+">
              <small class="form-text">Solo letras, números, guiones y puntos. 3-50 caracteres.</small>
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-envelope"></i> Email <span class="text-danger">*</span>
              </label>
              <input type="email" name="email" class="form-control" 
                     value="<?= old('email') ?>" required maxlength="100">
            </div>

            <div class="form-group full-width">
              <label class="form-label">
                <i class="bi bi-person-badge"></i> Nombre completo
              </label>
              <input type="text" name="full_name" class="form-control" 
                     value="<?= old('full_name') ?>" maxlength="100">
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-key"></i> Contraseña <span class="text-danger">*</span>
              </label>
              <div class="input-group">
                <input type="password" name="password" class="form-control" 
                       id="passwordInput" required minlength="8" 
                       pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}">
                <button type="button" class="btn btn-ghost" onclick="togglePassword('passwordInput')">
                  <i class="bi bi-eye" id="passwordIcon"></i>
                </button>
                <button type="button" class="btn btn-ghost" onclick="generatePassword()">
                  <i class="bi bi-shuffle"></i>
                </button>
              </div>
              <small class="form-text">Mínimo 8 caracteres, con mayúsculas, minúsculas y números.</small>
              <div class="mt-2">
                <div class="progress">
                  <div class="progress-bar" id="passwordStrength" style="width:0%"></div>
                </div>
                <small class="text-muted" id="passwordStrengthText">Ingresa una contraseña</small>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-key-fill"></i> Confirmar contraseña <span class="text-danger">*</span>
              </label>
              <input type="password" name="password_confirmation" class="form-control" 
                     id="passwordConfirm" required minlength="8">
              <small class="form-text text-danger" id="passwordMatchError" style="display:none">
                Las contraseñas no coinciden
              </small>
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-shield-lock"></i> Rol <span class="text-danger">*</span>
              </label>
              <select name="role" class="form-select" required>
                <option value="">— Seleccionar rol —</option>
                <?php foreach ($roles as $r): ?>
                  <option value="<?= e($r['name']) ?>" 
                          <?= old('role') === $r['name'] ? 'selected' : '' ?>>
                    <?= e($r['name']) ?>
                    <?php if ($r['description']): ?> — <?= e($r['description']) ?><?php endif; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">
                <i class="bi bi-toggle-on"></i> Estado
              </label>
              <select name="is_active" class="form-select">
                <option value="1" <?= old('is_active', '1') === '1' ? 'selected' : '' ?>>
                  ✓ Activo
                </option>
                <option value="0" <?= old('is_active') === '0' ? 'selected' : '' ?>>
                  ✗ Inactivo
                </option>
              </select>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex justify-end gap-2">
            <a class="btn btn-ghost" href="<?= url('admin/users') ?>">Cancelar</a>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-circle"></i> Crear usuario
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Panel lateral con ayuda -->
  <div class="col-lg-4">
    <div class="card card-accent">
      <div class="card-body">
        <h5><i class="bi bi-info-circle text-primary"></i> Ayuda</h5>
        <ul class="text-small" style="padding-left:1.25rem;line-height:1.8;color:var(--text-muted)">
          <li>El usuario recibirá un email de bienvenida si configuras SMTP</li>
          <li>Puedes cambiar la contraseña más tarde desde el perfil del usuario</li>
          <li>Los usuarios inactivos no pueden iniciar sesión</li>
          <li>El rol determina los permisos del usuario en el sistema</li>
        </ul>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5><i class="bi bi-shield-check text-success"></i> Recomendaciones de seguridad</h5>
        <ul class="text-small" style="padding-left:1.25rem;line-height:1.8;color:var(--text-muted)">
          <li>Usa una contraseña única y fuerte</li>
          <li>Activa 2FA para el nuevo usuario</li>
          <li>Asigna el rol mínimo necesario (principio de menor privilegio)</li>
          <li>Comunica las credenciales por un canal seguro</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
// ═══ Toggle password visibility ═══
function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  const icon = document.getElementById('passwordIcon');
  if (input.type === 'password') {
    input.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    input.type = 'password';
    icon.className = 'bi bi-eye';
  }
}

// ═══ Generate random password ═══
function generatePassword() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%';
  let password = '';
  // Asegurar al menos una mayúscula, una minúscula y un número
  password += 'ABCDEFGHJKLMNPQRSTUVWXYZ'[Math.floor(Math.random() * 24)];
  password += 'abcdefghjkmnpqrstuvwxyz'[Math.floor(Math.random() * 23)];
  password += '23456789'[Math.floor(Math.random() * 8)];
  for (let i = 0; i < 9; i++) {
    password += chars[Math.floor(Math.random() * chars.length)];
  }
  // Shuffle
  password = password.split('').sort(() => Math.random() - 0.5).join('');
  
  document.getElementById('passwordInput').value = password;
  document.getElementById('passwordConfirm').value = password;
  checkPasswordStrength(password);
  checkPasswordMatch();
}

// ═══ Password strength meter ═══
function checkPasswordStrength(password) {
  const bar = document.getElementById('passwordStrength');
  const text = document.getElementById('passwordStrengthText');
  let score = 0;
  
  if (password.length >= 8) score++;
  if (password.length >= 12) score++;
  if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
  if (/\d/.test(password)) score++;
  if (/[^a-zA-Z0-9]/.test(password)) score++;
  
  const levels = [
    { width: '0%', color: 'var(--border)', text: 'Ingresa una contraseña' },
    { width: '20%', color: 'var(--danger)', text: 'Muy débil' },
    { width: '40%', color: 'var(--warning)', text: 'Débil' },
    { width: '60%', color: 'var(--warning)', text: 'Aceptable' },
    { width: '80%', color: 'var(--success)', text: 'Fuerte' },
    { width: '100%', color: 'var(--success)', text: 'Muy fuerte' },
  ];
  
  const level = levels[score];
  bar.style.width = level.width;
  bar.style.background = level.color;
  text.textContent = level.text;
  text.style.color = level.color;
}

// ═══ Password match validation ═══
function checkPasswordMatch() {
  const pass = document.getElementById('passwordInput').value;
  const confirm = document.getElementById('passwordConfirm').value;
  const error = document.getElementById('passwordMatchError');
  
  if (confirm && pass !== confirm) {
    error.style.display = 'block';
  } else {
    error.style.display = 'none';
  }
}

// Event listeners
document.getElementById('passwordInput').addEventListener('input', function() {
  checkPasswordStrength(this.value);
  checkPasswordMatch();
});
document.getElementById('passwordConfirm').addEventListener('input', checkPasswordMatch);

// Validar antes de enviar
document.getElementById('userForm').addEventListener('submit', function(e) {
  const pass = document.getElementById('passwordInput').value;
  const confirm = document.getElementById('passwordConfirm').value;
  if (pass !== confirm) {
    e.preventDefault();
    alert('Las contraseñas no coinciden');
    document.getElementById('passwordConfirm').focus();
  }
});
</script>