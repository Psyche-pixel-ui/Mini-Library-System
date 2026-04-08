<?php include("mini_lib_db.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Library Management System</title>
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Optional custom overrides -->
  <link rel="stylesheet" href="tailwind.css">
</head>
<body class="bg-gray-100 text-gray-800">
  <header class="text-center py-6 bg-white shadow">
    <h1 class="text-3xl font-bold">Library Management Dashboard</h1>
    <?php
      $totalBooks = $conn->query("SELECT COUNT(*) AS total FROM books")->fetch_assoc()['total'];
      $borrowedBooks = $conn->query("SELECT COUNT(*) AS borrowed FROM books WHERE status='Borrowed'")->fetch_assoc()['borrowed'];
    ?>
    <div class="flex justify-center gap-10 mt-4 font-medium">
      <p>Total Books: <span class="text-blue-600"><?= $totalBooks ?></span></p>
      <p>Borrowed Books: <span class="text-red-600"><?= $borrowedBooks ?></span></p>
    </div>
  </header>

  <main class="max-w-5xl mx-auto mt-8 space-y-8">

    <!-- Student Management -->

    <section class="bg-white p-6 rounded-lg shadow">
      <h2 class="text-xl font-semibold border-b pb-2 mb-4">Student Management</h2>
      <form method="POST" class="flex gap-3 mb-4">
        <input type="text" name="studentName" placeholder="Student Name" required
          class="flex-1 px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-400">
        <button type="submit" name="addStudent"
          class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add Student</button>
      </form>
      <ul class="space-y-2">
        <?php
          if(isset($_POST['addStudent'])){
            $name = $_POST['studentName'];
            $conn->query("INSERT INTO students(name) VALUES('$name')");
          }
          $students = $conn->query("SELECT * FROM students");
          while($s = $students->fetch_assoc()){
            echo "<li class='bg-gray-50 p-2 rounded'>".$s['name']."</li>";
          }
        ?>
      </ul>
    </section>

    <!-- Book Management -->

    <section class="bg-white p-6 rounded-lg shadow">
      <h2 class="text-xl font-semibold border-b pb-2 mb-4">Book Management</h2>
      <form method="POST" class="flex gap-3 mb-4">
        <input type="text" name="bookTitle" placeholder="Book Title" required
          class="flex-1 px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-400">
        <button type="submit" name="addBook"
          class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Add Book</button>
      </form>
      <input type="text" id="searchBook" placeholder="Search Books..."
        class="w-full px-3 py-2 border rounded mb-4 focus:outline-none focus:ring focus:border-blue-400">
      <ul id="bookList" class="space-y-2">
        <?php
          if(isset($_POST['addBook'])){
            $title = $_POST['bookTitle'];
            $conn->query("INSERT INTO books(title,status) VALUES('$title','Available')");
          }
          $books = $conn->query("SELECT * FROM books");
          while($b = $books->fetch_assoc()){
            $color = $b['status'] === 'Available' ? 'text-green-600' : 'text-red-600';
            echo "<li class='bg-gray-50 p-2 rounded flex justify-between'>
                    <span>".$b['title']." - <span class='$color'>".$b['status']."</span></span>
                  </li>";
          }
        ?>
      </ul>
    </section>

    <!-- Borrowing System -->
     
    <section class="bg-white p-6 rounded-lg shadow">
      <h2 class="text-xl font-semibold border-b pb-2 mb-4">Borrowing System</h2>
      <form method="POST" class="flex gap-3 mb-4">
        <select name="studentId" required class="flex-1 px-3 py-2 border rounded">
          <?php
            $students = $conn->query("SELECT * FROM students");
            while($s = $students->fetch_assoc()){
              echo "<option value='".$s['id']."'>".$s['name']."</option>";
            }
          ?>
        </select>
        <select name="bookId" required class="flex-1 px-3 py-2 border rounded">
          <?php
            $books = $conn->query("SELECT * FROM books WHERE status='Available'");
            while($b = $books->fetch_assoc()){
              echo "<option value='".$b['id']."'>".$b['title']."</option>";
            }
          ?>
        </select>
        <button type="submit" name="borrowBook"
          class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Borrow Book</button>
      </form>
      <ul class="space-y-2">
        <?php
          if(isset($_POST['borrowBook'])){
            $studentId = $_POST['studentId'];
            $bookId = $_POST['bookId'];
            $conn->query("UPDATE books SET status='Borrowed' WHERE id=$bookId");
            $conn->query("INSERT INTO borrow_records(student_id,book_id) VALUES($studentId,$bookId)");
          }
          if(isset($_POST['returnBook'])){
            $bookId = $_POST['bookId'];
            $conn->query("UPDATE books SET status='Available' WHERE id=$bookId");
            $conn->query("DELETE FROM borrow_records WHERE book_id=$bookId");
          }
          if(isset($_POST['deleteRecord'])){
            $recordId = $_POST['recordId'];
            $conn->query("DELETE FROM borrow_records WHERE id=$recordId");
          }
          $records = $conn->query("SELECT r.id, s.name, b.title, b.id AS bookId 
                                   FROM borrow_records r 
                                   JOIN students s ON r.student_id=s.id 
                                   JOIN books b ON r.book_id=b.id");
          while($r = $records->fetch_assoc()){
            echo "<li class='bg-gray-50 p-2 rounded flex justify-between items-center'>
                    <span>".$r['name']." borrowed '".$r['title']."'</span>
                    <div class='flex gap-2'>
                      <form method='POST'>
                        <input type='hidden' name='bookId' value='".$r['bookId']."'>
                        <button type='submit' name='returnBook'
                          class='px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700'>Return</button>
                      </form>
                      <form method='POST'>
                        <input type='hidden' name='recordId' value='".$r['id']."'>
                        <button type='submit' name='deleteRecord'
                          class='px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700'>Delete</button>
                      </form>
                    </div>
                  </li>";
          }
        ?>
      </ul>
    </section>
  </main>
</body>
</html>
