
 $(document).ready(function () { 
function load_cart_(){
	 $(".cart-count").text(function(){
       $(this).load( "cart_count.php"); 
     }); 
};  
  

load_cart_();
 
 $("a.submit-cart").click(function(e){
        var product_id = $(this).attr( "product-info" );
        $.ajax({
            method: "POST",
            url: "add-product-to-cart-via-ajax.php",
            data: { inventory_product_id: product_id, qty: 1 }
                })
                .done(function( msg ) {
                load_cart_();
                });
                e.preventDefault();
            });
            
            
                 /* $("a.submit-cart").click(function(e){
                var product_id = $(this).attr( "product-info" );
                 //inventory_product_id
                //inventory_product_id
                $.ajax({
                    method: "POST",
                    url: "add-product-to-cart-via-ajax.php",
                    data: { inventory_product_id: product_id, qty: 1 }
                })
                    .done(function( msg ) {
                        $(".cart-count").text(msg);
                       
                    });
                e.preventDefault(); // Submit the form
            });
            */

});
