$(document).ready(function() {

  function checkLoginStatus(){
    return !!$.cookie('isLoggedIn'); //Replace with your cookie, localStorage check, or server request

}
    $(".btn-wishlist").click(function(event) {
      event.preventDefault(); 
      const productId = $(this).data("product-id");
     
      if(checkLoginStatus()){
      $.ajax({
        type: "POST",
        url: "add-product-to-wish-list.php", // Replace with your API endpoint
        //data: { product_id: productId },
        data: JSON.stringify({ product_id: productId }), // Stringify the data
        contentType: "application/json", // Important: set content type
        dataType: "json",    
        success: function(response) {
          console.log(response);
          // Handle successful addition to wishlist
          if (response.success) {
            $.ajax({
              url: "get_wishlist_count.php", //This file needs to be created
              type: "GET",
              success: function(data) {
                  $('.wishlist-count').text(data);
                  
              },
              error: function(error) {
                  console.error("Error updating wishlist count:", error);
              }
          });
          } else {
            alert("Error adding to wishlist: " + response.message);
          }
        },
        error: function(error) {
          // Handle AJAX errors
          alert("An error occurred while adding to wishlist.");
          

          console.error(error);
        }
      });
    }else {
      alert("Please log in to add items to your wishlist.");
  }
    });


  });
  