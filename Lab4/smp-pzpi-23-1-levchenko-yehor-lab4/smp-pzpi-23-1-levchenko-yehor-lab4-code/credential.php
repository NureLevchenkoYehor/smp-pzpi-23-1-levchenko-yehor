<?php require 'header.php' ?>

<form class="login-form" action="/credential/login" method="post">
    <!-- <label for="login">Login:</label>
    <input type="text" id="login" name="login" required>
    <br> -->
    <!-- <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <br> -->
    <label for="email">Email:</label>
    <input type="text" id="email" name="email" required>
    <br>
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    <?php if (isset($error) && !empty($error)): ?>
    <br>
        <p style="color: red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <br>
    <button type="submit">Submit</button>
</form>
<?php require 'footer.php' ?>