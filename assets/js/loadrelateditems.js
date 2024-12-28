$(document).ready(function(){
      load_data();

      function load_data(page, pid, cat)
      {

          pid = typeof pid !== 'undefined' ? pid : $('.main-product-cover').attr("product-info");
          cat = typeof cat !== 'undefined' ? cat : $('.main-product-cover').attr("product-cat");


           $.ajax({
                url:"text8.php",
                method:"POST",
                data:{page:page, pid:pid, cat:cat},
                success:function(data){
                    console.log(data);
                     $('#products').html(data);
                }
           })
      }

      $(".product-pager-next").on('click', function(e){
        $('.next-link').trigger('click.ll');
        e.preventDefault();
      });

      $(".product-pager-prev").on('click', function(e){
        $('.prev-link').trigger('click.ll');
        e.preventDefault();
      });

      $(document).on('click.ll', '.pagination_link', function(e){
           var page = $(this).attr("id");
           var pid = $(this).find('.main-product-cover').attr("product-info");
           var cat = $(this).find('.main-product-cover').attr("product-cat");

           load_data(page, pid, cat);

      });


 });