# 🚀 CBT Examination System (In Development)  
A secure, web-based platform for administering and taking computer-based tests.

---

## ✨ Current Features  
✅ **User Authentication**  
- Student registration/login  
- Session management (`session_check.php`)  

✅ **Exam Functionality**  
- Timed test interface (`take_exam.php`)  
- Automated submission (`submit_exam.php`)  

✅ **Admin Backend**  
- Initial admin setup (`create_admin.php`)  
- PDF report generation (TCPDF integration)  

---

## 🛠️ Tech Stack  
- **Frontend**: HTML, CSS, PHP  
- **Database**: MySQL (via `config.php` and `db_setup.php`)  
- **Dependencies**: TCPDF (for PDF reports)  

---

## 🚧 Upcoming Features  
◻ **Admin Dashboard**  
- Question bank management  
- Real-time analytics  

◻ **Enhanced Security**  
- Password encryption  
- Anti-cheating measures  

---

## 📦 Project Structure  
```plaintext
/includes/       # Core system files
  config.php
  db_setup.php  
  header.php
  footer.php

/student/        # Student interfaces
  login.php
  dashboard.php  
  take_exam.php

/vendor/tcpdf/   # PDF reporting
