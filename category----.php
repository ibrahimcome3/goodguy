<?php
include "include/conn.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=<device-width>, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div>
        <!--
    <form method="post" action="add-category.php">
                    <input type="text" class="b1" name="category" value="" placeholder="type a new sub category">
                    <input id="bccc" type="submit" value="submit" />
                    </form>
-->
        <form>
            <table>
                <tr>
                    <td><b>Category</b></td>
                    <td>
                    
                    <select id="sub1" class="sub1" name="sub1">
                    <option disabled selected value> -- select an option -- </option>
                        <?php
                        $sql = "select * from category_new where depth = 1";
                        $result = $mysqli->query($sql);
                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                ?>
                                                                   <option val= <?= $row['cat_id'] ?> ><?= $row['categoryName'] ?></option>
                                                                                                                                                                                                                                                                                <?php }
                        } ?>
                    </select>
                    <div><a href="#">click</a> to add new category</div>
              
                    <table>
                        <tr>
                            <td><input type="text" class="b1" name="category" value="" placeholder="type a new sub category"></td>
                            <td><input id="b" type="submit" value="submit" /></td>
                        </tr>
                        <tr><td><div id="b11"></div></td></tr>
                    </table>
                </td>
                    
                </tr>

                <tr>
                    <td><b>Sub category 1</b></td>
                    <td><select id="sub2" class="sub2" name="sub2" >
                        <option value="10000">Drinks</option>
                        <option value="20000">Clothes</option>
        
                    </select>
                    <div><a href="#" >click</a> to add new category</div>
                    <table>
                        <tr>
                            <td><input type="text" val="" class="b2" placeholder="type a new sub category"></td>
                            <td><input type="submit" id="b2" value="submit" /></td>
                        </tr>
                    </table>
                </td>
                    
                </tr>

                <tr>
                    <td><b>Sub category 2</b></td>
                    <td><select id="sub3" name="sub3" >
                        <option value="1">Drinks</option>
                        <option value="2">Clothes</option>
                        <option value="3">Alcholics</option>
                    </select>
                    <div><a href="#">click</a> to add new category</div>
                    <table>
                        <tr>
                            <td><input type="text" class="b3" placeholder="type a new sub category"></td>
                            <td><input type="submit" id="b3" value="submit" /></td>
                        </tr>
                    </table>
                </td>
                    <!--<td><input type="submit" value="add new category" /></td> -->
                </tr>
                <tr style="display:none;">
                    <td></td>
                    <td><input type="submit" class='submitCategoryForm' value="add a new category" /></td>
                </tr>
            </table>
        </form>
    </div>

    

</body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"    integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
            crossorigin="anonymous" referrerpolicy="no-referrer">
      </script>
    <script type="text/javascript">
$(document).ready(function () { 
    
        $("#b").click(function(e){
           let c =  $(".b1").val();
       
           $.ajax({
            url: 'add-category.php', 
           /* dataType: 'json',*/
            type: 'POST',
            data: {category: c},
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
            alert(123);
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
                console.log(data);
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


   


    
    </script>
</html>