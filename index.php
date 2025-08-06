<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EyeCheck | AI-Powered Eye Diagnosis</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/welcome.css" />
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold text-primary" href="#">EyeCheck</a>
      <div class="ms-auto">
        <a href="/eyecheck/login.php" class="btn btn-outline-primary fw-semibold px-4 py-2 shadow-sm" data-tooltip="Log in to your account">Log In</a>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->
  <section class="hero">
    <div class="container">
      <h1 class="animated-text">AI-Powered Conjunctivitis Detection</h1>
      <p>Upload an eye image and get instant diagnosis powered by deep learning.</p>
      <div class="btn-cta">
        <a href="./patient/register.php" class="btn btn-light text-primary">Get Started</a>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section class="features">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="feature-box">
            <img src="assets/images/secure.png" alt="Secure" width="60" />
            <h5>Secure & Private</h5>
            <p>Your data is encrypted and protected at every step.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-box">
            <img src="assets/images/user.webp" alt="User-Friendly" width="60" />
            <h5>User-Friendly</h5>
            <p>Simple upload process for patients and healthcare staff.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-box">
            <img src="assets/images/password.png" alt="AI Diagnosis" width="60" />
            <h5>Instant Results</h5>
            <p>Get a diagnosis result within seconds using our AI engine.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    &copy; 2025 EyeCheck. All rights reserved.
  </footer>

</body>
</html>
