<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>EyeCheck | AI-Powered Eye Diagnosis</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/welcome.css" />
</head>
<body>

  <!-- Navbar -->
 <nav class="navbar navbar-expand-lg navbar-glass sticky-top">
    <div class="container py-2">
      <a class="navbar-brand d-flex align-items-center gap-2" href="#">
        <img src="assets/images/logo.png" width="45px" alt="">
        <span>EyeCheck</span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
              aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse justify-content-end" id="mainNav">
        <ul class="navbar-nav align-items-lg-center gap-lg-3">
          <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
          <li class="nav-item"><a class="nav-link" href="#how">How it works</a></li>
          <li class="nav-item">
            <button id="themeToggle" class="btn btn-ghost rounded-pill px-3" type="button" aria-label="Toggle theme">
              <i class="bi bi-moon-stars"></i> <!-- swaps to sun on light -->
            </button>
          </li>
          <li class="nav-item">
            <a href="/eyecheck/login.php" class="btn btn-login px-4 py-2">Log In</a>
          </li>
        </ul>

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

  <!-- How It Works -->
  <section id="how" class="how-it-works">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">How it works</span>
        <h2 class="section-title">From Upload to AI Diagnosis in Seconds</h2>
        <p class="section-lead">
          Conjunctivitis Detection System uses Deep Learning to analyze eye images and return accurate predictions — all in a few simple steps.
        </p>
      </div>

      <div class="row g-4 hiw-steps">
        <div class="col-md-6 col-lg-3">
          <div class="step-card reveal" style="--i:1">
            <div class="step-num">1</div>
            <div class="step-icon"><i class="bi bi-upload"></i></div>
            <h5>Upload</h5>
            <p>Select an eye image and submit securely via the web interface (HTML, CSS, JS, PHP).</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="step-card reveal" style="--i:2">
            <div class="step-num">2</div>
            <div class="step-icon"><i class="bi bi-cpu"></i></div>
            <h5>AI Analysis</h5>
            <p>Our trained Deep Learning model processes the image server-side and checks for Conjunctivitis patterns.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="step-card reveal" style="--i:3">
            <div class="step-num">3</div>
            <div class="step-icon"><i class="bi bi-activity"></i></div>
            <h5>Prediction</h5>
            <p>Receive an instant prediction — Conjunctivitis or Not — with confidence, ready for decision-making.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="step-card reveal" style="--i:4">
            <div class="step-num">4</div>
            <div class="step-icon"><i class="bi bi-arrow-repeat"></i></div>
            <h5>Future-Ready</h5>
            <p>Architecture supports improved models, mobile optimization, and continuous accuracy upgrades.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Features -->
  <section id="features" class="features-adv">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">Features</span>
        <h2 class="section-title">Why EyeCheck stands out</h2>
        <p class="section-lead">
          Built with HTML, CSS, JavaScript, and PHP to deliver fast, secure, and accessible AI-powered diagnosis.
        </p>
      </div>

      <div class="row g-4">
        <div class="col-md-6 col-lg-4">
          <div class="feature-pro reveal" style="--i:1">
            <i class="bi bi-check2-circle"></i>
            <h5>Accurate Predictions</h5>
            <p>Deep Learning model trained on curated eye images for reliable conjunctivitis detection.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="feature-pro reveal" style="--i:2">
            <i class="bi bi-lock"></i>
            <h5>Secure & Private</h5>
            <p>Encrypted requests and safe handling of user data across the upload and prediction flow.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="feature-pro reveal" style="--i:3">
            <i class="bi bi-speedometer2"></i>
            <h5>Instant Results</h5>
            <p>Optimized backend delivers real-time responses within seconds after upload.</p>
          </div>
        </div>

        <div class="col-md-6 col-lg-4">
          <div class="feature-pro reveal" style="--i:4">
            <i class="bi bi-phone"></i>
            <h5>Mobile-Optimized</h5>
            <p>Responsive UI that works great on phones, tablets, and desktops — anywhere.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="feature-pro reveal" style="--i:5">
            <i class="bi bi-gear"></i>
            <h5>Extensible</h5>
            <p>Designed for future model upgrades and new features without breaking the flow.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-4">
          <div class="feature-pro reveal" style="--i:6">
            <i class="bi bi-diagram-3"></i>
            <h5>Clean Stack</h5>
            <p>Built with HTML, CSS, JavaScript, and PHP — easy to maintain and deploy.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    &copy; 2025 EyeCheck. All rights reserved.
  </footer>

  <script src="js/main.js"></script>
 

  

</body>
</html>
