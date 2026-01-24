<?php
require_once __DIR__ . '/../config/config.php';

?>

<!DOCTYPE html>
<html lang="ar" dir="ltr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://kit.fontawesome.com/64d58efce2.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <title>KCML / SLV</title>
  <style>
    body {
      font-family: 'Cairo', sans-serif;
      margin: 0;
      padding: 0;
      background: linear-gradient(to right, #e0f7ec, #a8e6cf);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .container {
      width: 100%;
      max-width: 400px;
      background: #fff;
      padding: 30px 25px;
      border-radius: 15px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
      text-align: center;
    }

    .container img {
      max-width: 120px;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
      margin-top: 20px;
    }

    .input-field {
      position: relative;
      width: 100%;
    }

    .input-field i {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #777;
      font-size: 16px;
      pointer-events: none;
    }

    .input-field input {
      width: 100%;
      box-sizing: border-box;
      /* ✅ يمنع الطلوع خارج الحدود */
      padding: 12px 40px 12px 12px;
      /* مكان للأيقونة */
      border: 1px solid #ddd;
      border-radius: 8px;
      outline: none;
      transition: border-color 0.3s ease;
      font-size: 14px;
    }

    .input-field input:focus {
      border-color: #0b6f76;
    }

    .btn {
      background: #0b6f76;
      color: #fff;
      padding: 12px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      transition: background 0.3s ease;
    }

    .btn:hover {
      background: #095c62;
    }

    p {
      margin-top: 10px;
      font-size: 14px;
      color: #555;
    }

    p a {
      color: #0b6f76;
      text-decoration: none;
      font-weight: bold;
    }

    p a:hover {
      text-decoration: underline;
    }

    /* Responsive */
    @media (max-width: 480px) {
      .container {
        padding: 20px;
        border-radius: 10px;
      }

      .container img {
        max-width: 90px;
      }

      .btn {
        font-size: 14px;
        padding: 10px;
      }
    }
  </style>
</head>

<body>

  <div class="container">
    <img src="images/logo.png" alt="Logo" style="max-width:210px;" />

    <h3 style="color: #0b6f76">Strong leadership visibility</h3>

    <form id="loginForm">
      <div class="input-field">
        <i class="fas fa-user"></i>
        <input type="email" name="email" placeholder="Email" required />
      </div>
      <div class="input-field">
        <i class="fas fa-lock"></i>
        <input type="password" name="password" placeholder="Password" required />
      </div>
      <input type="submit" value="Login" class="btn" />
    </form>
  </div>

  <?php if (!empty($error)): ?>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'خطأ',
        text: <?= json_encode($error) ?>,
        confirmButtonColor: '#0b6f76'
      });
    </script>
  <?php endif; ?>

  <script>
    document.getElementById("loginForm").addEventListener("submit", async (e) => {
      e.preventDefault();

      const formData = new FormData(e.target);
      const email = formData.get("email");
      const password = formData.get("password");

      try {
        const res = await fetch("../api/admin/auth.php?action=login", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          credentials: 'include',
          body: JSON.stringify({ email, password }),
        });

        const data = await res.json();

        if (data.success) {
          // حفظ بيانات المستخدم في session عبر AJAX آخر (أو باستخدام localStorage)
          const user = data.data.user;
          const token = data.data.token;

          // حفظ بالـ session عبر API مخصص
          await fetch("set_session.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ user, token }),
          });

          Swal.fire({
            icon: "success",
            title: "تم تسجيل الدخول",
            text: "جارٍ تحويلك...",
            timer: 1200,
            showConfirmButton: false
          });

          setTimeout(() => {
            if (user.role_id == 1) window.location.href = "dashboard.php";
            else if (user.role_id == 2) window.location.href = "requester/create_action.php";
            else if (user.role_id == 3) window.location.href = "dashboard.php";
            else if (user.role_id == 4) window.location.href = "dashboard.php";
            else if (user.role_id == 5) window.location.href = "dashboard.php";
            else if (user.role_id == 6) window.location.href = "dashboard.php";
            else window.location.href = "login.php";
          }, 1200);

        } else {
          Swal.fire({
            icon: "error",
            title: "فشل تسجيل الدخول",
            text: data.message || "تحقق من البريد الإلكتروني أو كلمة المرور"
          });
        }

      } catch (error) {
        console.log(error.message)
        Swal.fire({
          icon: "error",
          title: "خطأ في الاتصال",
          text: error.message || "حدث خطأ أثناء الاتصال بالخادم"
        });
      }
    });
  </script>


</body>

</html>