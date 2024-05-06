<?php
include 'components/connection.php';
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

// Delete item from cart
if (isset($_POST['delete_item'])) {
    $cart_id = $_POST['cart_id'];
    $cart_id = filter_var($cart_id, FILTER_SANITIZE_NUMBER_INT);

    $verify_delete_item = $conn->prepare("SELECT * FROM cart WHERE id = ?");
    $verify_delete_item->execute([$cart_id]);

    if ($verify_delete_item->rowCount() > 0) {
        $delete_cart_item = $conn->prepare("DELETE FROM cart WHERE id = ?");
        $delete_cart_item->execute([$cart_id]);
        $success_msg[] = "Cart item deleted successfully";
    } else {
        $warning_msg[] = "Cart item not found or already deleted";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <title>OnlineShop - Cart Page</title>
</head>

<body>
    <?php include 'components/header.php'; ?>

    <div class="main">
        <div class="banner">
            <h1>My cart</h1>
        </div>
        <div class="title2">
            <a href="home.php">Home</a><span> / Cart</span>
        </div>

        <section class="products">
            <h1 class="title">Products added in cart</h1>
            <div class="box-container">
            <?php
                $grand_total = 0;
                $select_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ?");
                $select_cart->execute([$user_id]);

                while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                    $product_id = $fetch_cart['product_id'];
                    $select_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
                    $select_product->execute([$product_id]);

                    if ($select_product->rowCount() > 0) {
                        $fetch_product = $select_product->fetch(PDO::FETCH_ASSOC);
                        $sub_total = $fetch_cart['qty'] * $fetch_product['price'];
            ?>
                        <form method="post" action="" class="box">
                            <input type="hidden" name="cart_id" value="<?= $fetch_cart['id']; ?>">
                            <img src="image/<?= $fetch_product['image']; ?>" class="img">
                            <h3 class="name"><?= $fetch_product['name']; ?></h3>
                            <div class="flex" id="cart">
                                <p class="price">Price $<?= $fetch_product['price']; ?>/-</p>
                            </div>
                            <button type="submit" name="delete_item" class="btn" id="btncart" onclick="return confirm('Delete this item?')">Delete</button>
                        </form>
            <?php
                        $grand_total += $sub_total;
                    } else {
                        echo '<p class="empty">Product was not found</p>';
                    }
                }
                ?>
            </div>
        </section>
    </div>

    <?php include 'components/footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script src="script.js"></script>
    <?php include 'components/alert.php'; ?>
</body>

</html>
