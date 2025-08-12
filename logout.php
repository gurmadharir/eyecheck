<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Logout | EyeCheck</title>

  <!-- Theme bootstrap CSS -->
  <script src="js/theme-init.js"></script>

  <link rel="stylesheet" href="css/global.css" />
  <link rel="stylesheet" href="css/theme.css" />
  <link rel="stylesheet" href="css/logout.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

</head>
<body onload="showToast()">
  <div class="logout-wrapper">
    <div class="logout-card">
      <i class="fas fa-check-circle logout-icon"></i>
      <h2>You’ve been logged out</h2>
      <p>Thanks for using EyeCheck. We hope to see you again soon!</p>
      <a href="login.php" class="logout-btn">Go to Login</a>
    </div>
  </div>

  <!-- Toast -->
  <div id="logoutToast" class="logout-toast">
    <i class="fas fa-door-open"></i> You have successfully logged out.
  </div>

  <script src="js/theme-toggle.js" defer></script>

  <script>
    function showToast() {
      const toast = document.getElementById("logoutToast");
      toast.classList.add("show");
      setTimeout(() => {
        toast.classList.remove("show");
      }, 3000);
    }
  </script>
</body>
</html>
