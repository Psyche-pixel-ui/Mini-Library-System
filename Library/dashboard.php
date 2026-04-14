<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Admin/auth.php");
    exit();
}

include("../Data/mini_lib_db.php");

/* ── DELETE BOOK ── */
if (isset($_POST['deleteBook'])) {
    $id   = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM books WHERE BookID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

/* ── ADD BOOK ── */
if (isset($_POST['addBook'])) {
    $title  = trim($_POST['title']);
    $author = trim($_POST['author']);
    $stmt   = $conn->prepare("INSERT INTO books (Title, Author, StatusID) VALUES (?, ?, 1)");
    $stmt->bind_param("ss", $title, $author);
    $stmt->execute();
}

/* ── ADD STUDENT ── */
if (isset($_POST['addStudent'])) {
    $fname  = trim($_POST['fname']);
    $lname  = trim($_POST['lname']);
    $email  = trim($_POST['email']);
    $course = trim($_POST['course']);
    $stmt   = $conn->prepare("INSERT INTO student (SFN, SLN, Email, Course) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $fname, $lname, $email, $course);
    $stmt->execute();
}

/* ── BORROW BOOK ── */
if (isset($_POST['borrowBook'])) {
    $studentId = (int)$_POST['studentId'];
    $bookId    = (int)$_POST['bookId'];
    $conn->query("UPDATE books SET StatusID = 2 WHERE BookID = $bookId");
    $conn->query("INSERT INTO borrow_records (StudentID, BookID, Borrow_Date) VALUES ($studentId, $bookId, CURDATE())");
}

/* ── RETURN BOOK ── */
if (isset($_POST['returnBook'])) {
    $bookId = (int)$_POST['bookId'];
    $conn->query("UPDATE books SET StatusID = 1 WHERE BookID = $bookId");
    $conn->query("UPDATE borrow_records SET Return_Date = CURDATE() WHERE BookID = $bookId AND Return_Date IS NULL");
}

/* ── Stats ── */
$totalBooks    = $conn->query("SELECT COUNT(*) AS total FROM books")->fetch_assoc()['total'];
$borrowedBooks = $conn->query("SELECT COUNT(*) AS n FROM books WHERE StatusID = 2")->fetch_assoc()['n'];

/* ── Data queries ── */
$students           = $conn->query("SELECT * FROM student ORDER BY StudentID DESC");
$books              = $conn->query("
    SELECT b.*, bs.Status_Name
    FROM books b
    JOIN book_status bs ON b.StatusID = bs.StatusID
    ORDER BY b.BookID DESC
");
$availableBooks     = $conn->query("SELECT * FROM books WHERE StatusID = 1 ORDER BY Title");
$allStudentsForBorrow = $conn->query("SELECT * FROM student ORDER BY SFN, SLN");
$records = $conn->query("
    SELECT br.*, s.SFN, s.SLN, b.Title, b.BookID
    FROM borrow_records br
    JOIN student s ON br.StudentID = s.StudentID
    JOIN books b ON br.BookID = b.BookID
    ORDER BY br.BorrowID DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Library Dashboard</title>
  <link rel="stylesheet" href="../style.css"/>
</head>
<body class="dashboard-body">

<!-- ══ HEADER ══════════════════════════════════════════════════════ -->
<header class="dash-header">
  <div class="dash-header-left">
    <h1>Library Management Dashboard</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?>
       &mdash; <?= htmlspecialchars($_SESSION['admin_role']) ?></p>
  </div>

  <div class="dash-header-right">
    <div class="stat-pill">
      Total Books: <span class="count blue"><?= $totalBooks ?></span>
    </div>
    <div class="stat-pill">
      Borrowed: <span class="count red"><?= $borrowedBooks ?></span>
    </div>
    <a href="../Admin/logout.php" class="logout-btn">Logout</a>
  </div>
</header>

<!-- ══ MAIN ═════════════════════════════════════════════════════════ -->
<main class="dash-main">

  <!-- ── STUDENT MANAGEMENT ── -->
  <section class="dash-card">
    <h2>Student Management</h2>

    <form method="POST" class="add-form">
      <input type="text"  name="fname"  placeholder="First Name" required/>
      <input type="text"  name="lname"  placeholder="Last Name"  required/>
      <input type="email" name="email"  placeholder="Email"      required/>
      <input type="text"  name="course" placeholder="Course (optional)"/>
      <button type="submit" name="addStudent" class="btn-add">Add Student</button>
    </form>

    <div>
      <?php if ($students->num_rows === 0): ?>
        <p class="empty-state">No students yet.</p>
      <?php else: ?>
        <?php while ($s = $students->fetch_assoc()): ?>
          <div class="list-item">
            <div class="list-item-info">
              <p><?= htmlspecialchars($s['SFN'] . ' ' . $s['SLN']) ?></p>
              <p><?= htmlspecialchars($s['Email']) ?>
                <?= $s['Course'] ? ' &bull; ' . htmlspecialchars($s['Course']) : '' ?>
              </p>
            </div>
            <span class="list-item-meta">Borrow limit: <?= (int)$s['MaxBorrowLimit'] ?></span>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- ── BOOK MANAGEMENT ── -->
  <section class="dash-card">
    <h2>Book Management</h2>

    <form method="POST" class="add-form">
      <input type="text" name="title"  placeholder="Book Title" required/>
      <input type="text" name="author" placeholder="Author"     required/>
      <button type="submit" name="addBook" class="btn-add green">Add Book</button>
    </form>

    <div>
      <?php if ($books->num_rows === 0): ?>
        <p class="empty-state">No books yet.</p>
      <?php else: ?>
        <?php while ($b = $books->fetch_assoc()): ?>
          <div class="list-item">
            <div class="list-item-info">
              <p><?= htmlspecialchars($b['Title']) ?></p>
              <p><?= htmlspecialchars($b['Author']) ?></p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <span class="status-badge <?= $b['StatusID'] == 1 ? 'status-available' : 'status-borrowed' ?>">
                <?= htmlspecialchars($b['Status_Name']) ?>
              </span>
              <?php if ($b['StatusID'] == 1): ?>
                <form method="POST" style="margin:0;">
                  <input type="hidden" name="id" value="<?= $b['BookID'] ?>"/>
                  <button type="submit" name="deleteBook" class="btn-delete">Delete</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- ── BORROWING SYSTEM ── -->
  <section class="dash-card">
    <h2>Borrowing System</h2>

    <form method="POST" class="add-form">
      <select name="studentId" required>
        <option value="">Select Student</option>
        <?php while ($s = $allStudentsForBorrow->fetch_assoc()): ?>
          <option value="<?= $s['StudentID'] ?>">
            <?= htmlspecialchars($s['SFN'] . ' ' . $s['SLN']) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <select name="bookId" required>
        <option value="">Select Available Book</option>
        <?php while ($ab = $availableBooks->fetch_assoc()): ?>
          <option value="<?= $ab['BookID'] ?>">
            <?= htmlspecialchars($ab['Title']) ?>
          </option>
        <?php endwhile; ?>
      </select>

      <button type="submit" name="borrowBook" class="btn-add purple">Borrow</button>
    </form>

    <div>
      <?php if ($records->num_rows === 0): ?>
        <p class="empty-state">No borrow records yet.</p>
      <?php else: ?>
        <?php while ($r = $records->fetch_assoc()): ?>
          <div class="list-item">
            <div class="list-item-info">
              <p><?= htmlspecialchars($r['SFN'] . ' ' . $r['SLN']) ?>
                 &mdash; "<?= htmlspecialchars($r['Title']) ?>"</p>
              <p>Borrowed: <?= htmlspecialchars($r['Borrow_Date']) ?>
                <?= $r['Return_Date'] ? ' &bull; Returned: ' . htmlspecialchars($r['Return_Date']) : '' ?>
              </p>
            </div>
            <?php if (!$r['Return_Date']): ?>
              <form method="POST" style="margin:0;">
                <input type="hidden" name="bookId" value="<?= $r['BookID'] ?>"/>
                <button type="submit" name="returnBook" class="btn-return">Return</button>
              </form>
            <?php else: ?>
              <span class="status-badge status-available">Returned</span>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </section>

</main>
</body>
</html>