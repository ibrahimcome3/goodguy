$(document).ready(function(){
      load_data();

      function load_data(page,pid)
      {
          var pid = typeof pid !== 'undefined' ? pid : $('.main-product-cover').attr("product-info");
         //  alert(pid)
           $.ajax({
                url:"test.php",
                method:"POST",
                dataType: "json",
                data:{page:page, pid:pid},
                  beforeSend: function() {
                    $('#main-review').html("").css('min-height', '600px');

                    $('#loading-spinner').show();
                  },
                   complete: function() {
                    $('#loading-spinner').hide();
                    $('#main-review').css({ 'min-height' : '' });
                  },
                success:function(data){
                     var str = '';
                     $.each(data, function(key, value) {
                        console.log(value.rating);
                         if(value.rating !== undefined){
                         var rating;
                       switch(value.rating) {
                          case 'good':
                             rating = 70;
                            break;
                          case 'excellent':
                            rating = 100;
                            break;
                          case 'ok':
                            rating = 50;
                            break;
                          case 'bad':
                            rating = 10;
                            break;
                          case 'great':
                            rating = 60;
                            break;
                          default:
                            rating = 0;
                            // code block
                        }

               str = '<div class="review"><div class="row no-gutters"><div class="col-auto"><h4><a href="#">'+ value.customer_fname +' '+ value.customer_lname +'</a></h4>'+
                     '<div class="ratings-container"><div class="ratings"><div class="ratings-val" style="width: '+rating+'%;"></div>'+
                      '</div></div><span class="review-date">'+value.date_created+'</span></div><div class="col">'+
                      '<h4>'+value.rating+'</h4><div class="review-content"><p>'+value.review_text+'</p></div>'+
                      '</div></div></div>';
                      $('#main-review').append(str);
                  }
                  });
                      if(data.length > 1){
                      $('#main-review').append(data[data.length-1]);
                      }

                }
           })
      }



    $(document).on('click.ll', '.pagination_linker', function(e){
           var page = $(this).attr("id");
           var pid = $(this).find('.main-product-cover').attr("product-info");
           load_data(page,pid);
           e.preventDefault();

      });


 });''