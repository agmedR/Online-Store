<?php
include 'components/connection.php';
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

// Handle logout request
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit; // Ensure script stops after redirection
}

// Handle adding products to wishlist
if (isset($_POST['add_to_wishlist'])) {
    $id = unique_id();
    $product_id = $_POST['product_id'];

    // Check if the product already exists in wishlist or cart
    $check_product = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check_product->execute([$user_id, $product_id]);

    if ($check_product->rowCount() > 0) {
        $warning_msg[] = 'Product already exists in your wishlist';
    } else {
        // Retrieve product price
        $select_price = $conn->prepare("SELECT price FROM products WHERE id = ? LIMIT 1");
        $select_price->execute([$product_id]);
        $fetch_price = $select_price->fetch(PDO::FETCH_ASSOC);

        if ($fetch_price) {
            // Insert into wishlist
            $insert_wishlist = $conn->prepare("INSERT INTO wishlist (id, user_id, product_id, price) VALUES (?,?,?,?)");
            $insert_wishlist->execute([$id, $user_id, $product_id, $fetch_price['price']]);
            $success_msg[] = 'Product added to wishlist successfully';
        } else {
            $warning_msg[] = 'Product not found or invalid product ID';
        }
    }
}

// Handle adding products to cart
if (isset($_POST['add_to_cart'])) {
    $id = unique_id();
    $product_id = $_POST['product_id'];

    $qty = $_POST['qty']; // تأكد من أن القيمة تكون رقمية للكمية
    $qty = filter_var($qty, FILTER_SANITIZE_NUMBER_INT); // تنظيف القيمة لتكون رقمية

    $verify_cart = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $verify_cart->execute([$user_id, $product_id]);

    $count_cart_items = $conn->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $count_cart_items->execute([$user_id]);
    $cart_item_count = $count_cart_items->fetchColumn();

    if ($verify_cart->rowCount() > 0) {
        $warning_msg[] = 'Product already exists in your cart';
    } elseif ($cart_item_count >= 20) {
        $warning_msg[] = 'Your cart is full. Maximum 20 items allowed.';
    } else {
        $select_price = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $select_price->execute([$product_id]);
        $fetch_price = $select_price->fetch(PDO::FETCH_ASSOC);

        if ($fetch_price) {
            $insert_cart = $conn->prepare("INSERT INTO cart (id, user_id, product_id, price, qty) VALUES (?,?,?,?,?)");
            $insert_cart->execute([$id, $user_id, $product_id, $fetch_price['price'], $qty]);
            $success_msg[] = 'Product added to cart successfully';
        } else {
            $warning_msg[] = 'Product not found or invalid product ID';
        }
    }
}


?>

<style type="text/css">
    <?php include 'style.css'; ?>
</style>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>OnlineShop - product detail page</title>
</head>

<body>
    <?php include 'components/header.php'; ?>
    <div class="main">
        <div class="banner">
            <h1>product detail</h1>
        </div>
        <div class="title2">
            <a href="home.php">Home </a><span>/ product detail</span>
        </div>
        <section class="view_page">
            <?php
                if (isset($_GET['pid'])){
                    $pid = $_GET['pid'];
                    $select_products = $conn->prepare("SELECT * FROM products WHERE id = '$pid'");
                    $select_products->execute();
                    if ($select_products->rowCount() > 0) {
                        while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {

                       
            ?>
            <form method="post">
                <img src="image/<?php echo $fetch_products['image']; ?>">
                <div class="detail">
                    <div class="price">$<?php echo $fetch_products['price']; ?>/-</div>
                    <div class="name"><?php echo $fetch_products['name']; ?></div>
                    <div class="detail">
                        <p>Lorem, ipsum dolor sit amet consectetur adipisicing elit. Alias quaerat, officiis pariatur fugiat possimus neque! Id, sequi labore impedit accusantium ducimus at quos dolorum aperiam non. Ut iusto cum sequi?</p>

                    </div>
                    <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">
                    <div class="button">
                        <button type="submit" name="add_to_wishlist" class="btn">add to wishlist<i class="bx bx-heart"></i></button>
                        <input type="hidden" name="qty" value="1" min="0" class="quantity">
                        <button type="submit" name="add_to_cart" class="btn">add to cart<i class="bx bx-cartt"></i></button>
                    </div>
                </div>
            </form>
            <?php
                         }
                    }
                }
            ?>
        </section>
        <?php include 'components/footer.php'; ?>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script src="script.js"></script>
    <?php include 'components/alert.php'; ?>
</body>

</html>
