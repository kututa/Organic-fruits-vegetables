
<?php
require_once __DIR__ . '/includes/auth.php';
if (is_admin()) header('Location: index.php');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>

  <!-- ✅ Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
        rel="stylesheet" 
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" 
        crossorigin="anonymous">

</head>
<body class="bg-light">

  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4">
            <h3 class="text-center mb-4">Admin Login</h3>

            <?php if(!empty($login_error)): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>

            <form method="post">
              <input type="hidden" name="login_action" value="1">

              <div class="mb-3">
                <label class="form-label">Username</label>
                <input class="form-control" name="username" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Password</label>
                <input class="form-control" name="password" type="password" required>
              </div>

              <button class="btn btn-primary w-100">Login</button>
            </form>
              <hr>
              <h5 class="text-center mt-3">Create local account</h5>
              <?php if(!empty($register_error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($register_error) ?></div>
              <?php endif; ?>
              <?php if(!empty($register_success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($register_success) ?></div>
              <?php endif; ?>

              <form method="post">
                <input type="hidden" name="register_action" value="1">
                <div class="mb-2">
                  <input class="form-control" name="name" placeholder="Full name">
                </div>
                <div class="mb-2">
                  <input class="form-control" name="email" placeholder="Email">
                </div>
                <div class="mb-2">
                  <input class="form-control" name="password" type="password" placeholder="Password">
                </div>
                <div class="mb-2">
                  <input class="form-control" name="password_confirm" type="password" placeholder="Confirm password">
                </div>
                <button class="btn btn-outline-secondary w-100">Create local account</button>
              </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ✅ Bootstrap JS Bundle (for modals, alerts, etc.) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
          integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" 
          crossorigin="anonymous"></script>

</body>
</html>

<?php include __DIR__ . '/includes/footer.php'; ?>
```
