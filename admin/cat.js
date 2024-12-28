$(document).ready(function () { 
    
        $("#b").click(function(e){
           let c =  $(".b1").val();
       
           $.ajax({
            url: 'add-category.php', 
           /* dataType: 'json',*/
            type: 'POST',
            data: {category: c},
            success: function(data, status, xhr) {
                alert(data);
                $("#b11").html("inserted the data");
            },
            error: function(xhr, status, error) {
                alert(status);
            }
          });
        e.preventDefault();

        });

        $("#b2").click(function(e){
           let c =  $(".b2").val();
        e.preventDefault();

        });

        $(".submitCategoryForm").click(function(e){
            var firstselect = $('#sub1').find(":selected").text();
            var secondselect = $('#sub2').find(":selected").text();
            var thirdselect = $('#sub3').find(":selected").text();

            console.log(typeof(firstselect));
            console.log(typeof(secondselect));
            console.log(thirdselect.length);
            if(thirdselect.length == 0){
                thirdselect = null; 
            }

            console.log(thirdselect);


            $.ajax({
        url: 'categoryProcessor.php', 
        type: 'POST',
        /*dataType: 'json',*/
        data: {firstselect : firstselect, secondselect: secondselect, thirdselect: thirdselect},
        success: function(data, status, xhr) {
          
            console.log(data);
         
        },
        error: function(xhr, status, error) {
             console.log(status);
        }
    });    

        e.preventDefault();
        });
    
    $(document).on('change', 'select.sub1', function() {
    console.log($(this).val()); // the selected options’s value
    let str = [];
    let arrayString = '';
    var cat_1_name = $('#sub1').find(":selected").text();
   
    $.ajax({
        url: 'sub2.php', 
        type: 'POST',
        dataType: 'json',
        data: {cat_1_name : cat_1_name},
        success: function(data, status, xhr) {
            arrayString += "<option></option>";
            $.each(data, function(key,valueObj){
               arrayString += "<option value = '"+ key +"' >"+ valueObj +"</option>";
            });
            console.log(arrayString);
            $("#sub2").html(arrayString);
        },
        error: function(xhr, status, error) {
             alert(status);
        }
    });    
    var opt = $(this).find('option:selected')[0];   
    });


    $(document).on('change', 'select.sub2', function() {
    console.log($(this).val()); // the selected options’s value
    let str = [];
    let arrayString = '';

    var sub_cat_1_name = $('#sub2').find(":selected").text();
    $.ajax({
        url: 'sub3.php', 
        type: 'POST',
        dataType: 'json',
        data: {sub_cat_1_name:sub_cat_1_name},
        success: function(data, status, xhr) {
            arrayString += "<option></option>";
            $.each(data, function(key,valueObj){

               arrayString += "<option value = '"+ key +"' >"+ valueObj +"</option>";
            });
            console.log(arrayString);
            $("#sub3").html(arrayString);
        },
        error: function(xhr, status, error) {
             alert(status);
        }
    });    
    var opt = $(this).find('option:selected')[0];   
    });
   
  //  $(document).on('change', '.input.sub1', function() {
   //  alert(1);
   // });



   $("#b2").click(function(e){
          
           //$('select[name^="sub1"] option:selected').attr("selected",null);
           //let opt = $('select[name^="sub1"] option[value=result[0]]').attr("selected","selected");
           //let opt = $("#sub1 ").find('option:selected')[0].val();

           let c =  $(".b2").val();
           //alert($('#sub1 :selected').attr('value'));
           var secondselect = $('#sub1').find(":selected").attr('val');
           var sub_cat_1_name = $('#sub1').find(":selected").text();

           $.ajax({
            url: 'add-sub-cat-1.php', 
           /* dataType: 'json',*/
            type: 'POST',
            data: {category: c, sub_category_1 : secondselect, sub_category_1_name: sub_cat_1_name},
            success: function(data, status, xhr) {
                alert(data);
                $("#b11").html("inserted the data");
            },
            error: function(xhr, status, error) {
                alert(status);
            }
          });

        e.preventDefault();

        });


        $("#b3").click(function(e){
           //$('select[name^="sub1"] option:selected').attr("selected",null);
           //let opt = $('select[name^="sub1"] option[value=result[0]]').attr("selected","selected");
           //let opt = $("#sub1 ").find('option:selected')[0].val();

           let c =  $(".b3").val();
           //alert($('#sub1 :selected').attr('value'));
           var sub_cat_1 = $('#sub1').find(":selected").attr('val');
           var sub_cat_1_name = $('#sub1').find(":selected").text();

           var sub_category_2 = $('.sub2').find(":selected").attr('value');
           var sub_cat_2_name = $('#sub2').find(":selected").text();


            alert(sub_category_2);
           

           $.ajax({
            url: 'add-sub2-cat-2.php', 
           /* dataType: 'json',*/
            type: 'POST',
            data: {category: c, sub_category_1 : sub_cat_1, sub_category_1_name: sub_cat_1_name, sub_category_2 : sub_category_2, sub_cat_2_name:sub_cat_2_name},
            success: function(data, status, xhr) {
                console.log(data);
                $("#b11").html("inserted the data");
            },
            error: function(xhr, status, error) {
                alert(status);
            }
          });

        e.preventDefault();

        });   


    });
