
	
	<!-- Plugins JS File -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js" type="text/javascript"></script> 
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.hoverIntent.min.js"></script>
    <script src="assets/js/jquery.waypoints.min.js"></script>
    <script src="assets/js/superfish.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/js/bootstrap-input-spinner.js"></script>
    <script src="assets/js/jquery.magnific-popup.min.js"></script>
    <script src="assets/js/jquery.plugin.min.js"></script>
    <script src="assets/js/jquery.countdown.min.js"></script>
    <script src="assets/js/jquery.countTo.js"></script>
	 <script src="assets/js/jquery.elevateZoom.min.js"></script>
    <!-- Main JS File -->
    <script src="assets/js/demos/demo-13.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="login.js"></script>
 <!--  <script src="cart.js"></script> -->
  
    <script src="assets/js/reviewjs.js"></script>
    <script src="assets/js/add-cart.js"></script>
    
	

    
<script>
 $(document).ready(function () { 
     console.log('alert 1');
load_cart_();     
$(".cart-total-price").text(function(){
        $(this).load( "cart_total_cost.php");
}); 
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
                $(".cart-total-price").text(function(){
                      $(this).load( "cart_total_cost.php");
                });  
                });
                e.preventDefault();
            });
            
        $("button.update_cart").click(function(event){
                $("form.update_cart_form").submit();
                event.preventDefault() // Submit the form
            });


            $("button.btn-remove").click(function(event){
                   event.preventDefault();
                   var del_title =  $(this).attr('cart-item-id');
                   var item_to_removed = $(this).closest('tr');
                $.ajax({
                    type: 'POST',
                    cache: false,
                    url: 'remove_product_from_cart.php',
                    dataType: "json",
                    data: {remove:del_title},
                    success: function(data) {
                         item_to_removed.remove();
                         location.reload(true);
                    }
                });
            });            
            
});


 
</script>







    <!-- Main JS File -->

