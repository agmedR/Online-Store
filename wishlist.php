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

// Handle adding products to cart
if (isset($_POST['add_to_cart'])) {
    $id = unique_id();
    $product_id = $_POST['product_id'];

    $qty = 1; // تأكد من أن القيمة تكون رقمية للكمية
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

// Delete item from wishlist
if (isset($_POST['delete_item'])) {
    $wishlist_id = $_POST['delete_item'];
    $wishlist_id = filter_var($wishlist_id, FILTER_SANITIZE_NUMBER_INT); // تأكيد أن المدخلات هي أرقام صحيحة

    $verify_delete_item = $conn->prepare("SELECT * FROM wishlist WHERE id = ?");
    $verify_delete_item->execute([$wishlist_id]);

    if ($verify_delete_item->rowCount() > 0) {
        $delete_wishlist_id = $conn->prepare("DELETE FROM wishlist WHERE id = ?");
        $delete_wishlist_id->execute([$wishlist_id]);
        $success_msg[] = "Wishlist item deleted successfully";
    } else {
        $warning_msg[] = "Wishlist item not found or already deleted";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>OnlineShop - Wishlist Page</title>
    <style>
        <?php include 'style.css'; ?>
    </style>
</head>

<body>
    <?php include 'components/header.php'; ?>

    <div class="main">
        <div class="banner">
            <h1>My Wishlist</h1>
        </div>
        <div class="title2">
            <a href="home.php">Home</a><span> / Wishlist</span>
        </div>

        <section class="products">
            <h1 class="title">Products added in Wishlist</h1>
            <div class="box-container">
                <?php
                $grand_total = 0;
                $select_wishlist = $conn->prepare("SELECT * FROM wishlist WHERE user_id = ?");
                $select_wishlist->execute([$user_id]);

                while ($fetch_wishlist = $select_wishlist->fetch(PDO::FETCH_ASSOC)) {
                    $product_id = $fetch_wishlist['product_id'];
                    $select_products = $conn->prepare("SELECT * FROM products WHERE id = ?");
                    $select_products->execute([$product_id]);

                    if ($select_products->rowCount() > 0) {
                        $fetch_products = $select_products->fetch(PDO::FETCH_ASSOC);
                ?>
                        <form method="post" action="" class="box">
                            <input type="hidden" name="wishlist_id" value="<?= $fetch_wishlist['id']; ?>">
                            <img src="image/<?= $fetch_products['image']; ?>" class="img">
                            <div class="button">
                                <button type="submit" name="add_to_cart"><i class="bx bx-cart"></i></button>
                                <a href="view_page.php?pid=<?= $fetch_products['id']; ?>" class="bx bx-show"></a>
                                <button type="submit" name="delete_item" value="<?= $fetch_wishlist['id']; ?>" onclick="return confirm('Delete this item?');"><i class="bx bx-x"></i></button>
                            </div>
                            <h3 class="name"><?= $fetch_products['name']; ?></h3>
                            <input type="hidden" name="product_id" value="<?= $fetch_products['id']; ?>">
                            <div class="flex" id="wishlist">
                                <p class="price">Price $<?= $fetch_products['price']; ?>/-</p>
                            </div>
                            <a href="checkout.php?get_id=<?= $fetch_products['id']; ?>" class="btn">Buy Now</a>
                        </form>
                <?php
                        $grand_total += $fetch_wishlist['price'];
                    } else {
                        echo '<p class="empty">No products added yet!</p>';
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
