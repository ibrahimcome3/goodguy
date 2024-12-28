<?php
include "includes.php";
 $cat = new Category();
                                                    

                                                    // if($stmt->rowCount() > 0){
                                                     $stmt = $cat->get_cat();
                                                    // }
 
                                                
                                                
                                                while ($row = $stmt->fetch()) {  
                                                    
                                                    
                                                    var_dump($row['categoryName']) ;  echo "<br/>";
                                                             $sub_cat = new Category();
                                                                 $stmt_ = $cat->sub_get_cat($row['categoryName']);
                                                         while ($row_ = $stmt_->fetch()) {  
                                                              echo ($row_['categoryName']) ;  echo "<br/>";
                                                             
                                                         }  
                                                             
                                                    
                                                    
                                                    
                                                    
                                                }
                                                
                                                
                                                
                                           




?>