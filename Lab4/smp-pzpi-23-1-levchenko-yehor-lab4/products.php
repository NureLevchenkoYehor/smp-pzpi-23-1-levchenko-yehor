<?php require 'header.php' ?>

<form action="/cart/add_batch" method="post" class="product-list">
    <?php 
    /** @var array $products */
    ?>
    <?php foreach ($products as $i => $product): 
        $id = htmlspecialchars($product->product_id);
        $name = htmlspecialchars($product->product_name);
        $price = htmlspecialchars($product->product_price);
        $currency_symbol = htmlspecialchars($product->currency_symbol);
        $image_url = htmlspecialchars($product->image_url);
        $image_name = htmlspecialchars($product->image_name);
        ?>
        
        <div class="product-item">
            <input type="hidden" name="products[<?= $i ?>][id]" value="<?= $id ?>">
            <input type="hidden" name="products[<?= $i ?>][name]" value="<?= $name ?>">
            <input type="hidden" name="products[<?= $i ?>][price]" value="<?= $price ?>">
            <img src="<?= $image_url ?>" alt="<?= $image_name ?>">
            <p class="product-label"><?= $name ?></p>
            <input class="quantity-input" type="number" min="0" max="99" name="products[<?= $i ?>][quantity]" value="0">
            <p class="product-price"><?= "{$price}{$currency_symbol}" ?></p>
        </div>
    <?php endforeach; ?></div>
    <button class="submit-button" type="submit">Send</button>
</form>

<?php require 'footer.php' ?>