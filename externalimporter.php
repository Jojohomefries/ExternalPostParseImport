<?php
include( __DIR__ . '/simple_html_dom.php');


?>



				<h1>Parse</h1>

				<?php

				global $wpdb;

				$URL_TO_PARSE = 'url_to_parse';

				//get array of current IDs in system
				$idsInSystem = array();

				$mydb = new wpdb('posting','password','database','localhost'); //live
				$rows = $mydb->get_results("select external_id from postings where external_id is not null"); //collect previous imports

				$mydb->show_errors = TRUE;
				$mydb->suppress_errors = FALSE;
				echo 'lqry: '.$mydb->last_query;
				echo 'The number of external posts is: '.sizeof($rows);
				echo "<ul>";
				foreach ($rows as $obj) :
					echo "<li>".$obj->external_id."</li>";
					array_push($idsInSystem, $obj->external_id);
				endforeach;
				echo "</ul><br><br>";


					function produce_XML_object_tree($raw_XML) {
					    libxml_use_internal_errors(true);
					    try {
					        $xmlTree = new SimpleXMLElement($raw_XML);
					    } catch (Exception $e) {
					        // Something went wrong.
					        $error_message = 'SimpleXMLElement threw an exception.';
					        foreach(libxml_get_errors() as $error_line) {
					            $error_message .= "\t" . $error_line->message;
					        }
					        trigger_error($error_message);
					        return false;
					    }
					    return $xmlTree;
					}

					function get_email_route($value) {
						//implement switch to find post email

						return '';
					}

					function get_full_part($text) {
						if ( html_entity_decode($text) === 'identifier') {
							return 2;
						} else {
							return 1;
						}
					}

					$xml_feed_url = $URL_TO_PARSE;
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $xml_feed_url);
					curl_setopt($ch, CURLOPT_HEADER, false);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$xml = curl_exec($ch);
					curl_close($ch);

					$cont = simplexml_load_string($xml,'SimpleXMLElement',LIBXML_NOCDATA);

					$set = $cont->channel;

					echo '<pre>';
					$vars = get_object_vars($set);
					$items = $vars['item'];
					echo '</pre>';

					$newIds = array();
					foreach ($items as $key => $value) {
						$ts = $value->title;
						$tarr = explode(' | ', $ts);
						array_push($newIds, $tarr[1]);
					}
				
					//delete missing postings	
					$mydb->query( "DELETE FROM postings WHERE external_id is not null AND external_id NOT IN ( '" . implode($newIds, "', '") . "' ) AND paidEndDate < CURRENT_DATE()" );
					



					foreach ($items as $key => $value) {


						

						$titlestring = $value->title;
						$titleArr = explode(' | ', $titlestring);
						$link = trim($value->link);
						$descrip = trim($value->description);

						$html = file_get_html($link);


						// Find all "A" tags and print their HREFs
						$text = '';
						$string_data_to_remove = '';

						foreach($html->find('a.indentifier') as $e)  {
							$text = $e->href;
							$text = strip_tags($text);
							$text = str_replace($string_data_to_remove, '', $text);
							$text = trim(substr($text, 0, strpos($text, "&subject")));
						}

						if ($text == '') {
								foreach($html->find('a') as $e)  {
									if (strpos($e->href, 'mailto:') !== false) {
									    $text = str_replace('mailto:', '', $e->href);
									}
								
							}
						}

						if ($text == '') {
							echo 'No Email Found';
							echo '<br><br>';

							//skip this import
							continue;
						}

						//page parse for full description
						$ret = $html->find('div[class="post_description"]', 0);
						$descrip = html_entity_decode($ret->innertext);

						//We have enough data to post in db

						$h3 = array();
						foreach($html->find('.identifier h3') as $e)  {
							array_push($h3, $e->plaintext);
						}
							
							$category = get_email_route($text);
							$type = get_full_part($text);


						

						//INSERT
							//create values
							$post_guid = util::genGuid('postGuid');
					        $edit_guid = util::genGuid('editGuid');
					        $firstName = '';
					        $lastName = '';
					        $phone = '';
					        $email = html_entity_decode($text);
					        $companyName = 'UPDATEME';
					        $companyUrl = '';
					        $postCategory = 'post';
					        $postEmploymentTypeID = $type;
					        $postLocationID = 1;
					        $postTitle = $titleArr[0].' - '.$titleArr[1];
					        $external_id = $titleArr[1];
					        $postCompanyName = 'UPDATEME';
					        $postImageFilename = 'UPDATEME.png';
					        $postImageWidth = '300';
					        $postImageHeight = '107';
					        $postDescription = $descrip;
					        $postSalary = $sal;
					        $postContact = 'UPDATEME';
					        //$postUrl1 = $link;
					        $postUrl1 = 'UPDATEME';
					        $postDisplayEndDate = '0000-00-00';
					        $createDT = date(DATE_MYSQLDT);
					        $postSubCategoryID1 = $category;
					        $paidStartDate = date("Y-m-d");
					        $paidEndDate = date("Y-m-d",strtotime("+1 month"));

						
						
							if (in_array($titleArr[1], $idsInSystem)) {

								//this id is already in the system, do not import
								echo 'this id is in the system';

							} else {

							echo $mydb->insert("postings", array(
							"post_guid" => $post_guid,
							"edit_guid" => $edit_guid,
							"firstName" => $firstName,
							"lastName" => $lastName,
							"email" => $email,
							"phone" => $phone,
							"companyName" => $companyName,
							"companyUrl" => $companyUrl,
							"postCategory" => $postCategory,
					        "postEmploymentTypeID" => $postEmploymentTypeID,
					        "postLocationID" => $postLocationID,
					        "postTitle" => $postTitle,
					        "postCompanyName" => $postCompanyName,
					        "postImageFilename" => $postImageFilename,
					        "postImageWidth" => $postImageWidth,
					        "postImageHeight" => $postImageHeight,
					        "postDescription" => $postDescription,
					        "postSalary" => $postSalary,
					        "postContact" => $postContact,
					        "postUrl1" => $postUrl1,
					        "postDisplayEndDate" => $postDisplayEndDate,
					        "createDT" => $createDT,
					        "postSubCategoryID1" => $postSubCategoryID1,
					        "external_id" => $external_id,
					        "paidStartDate" => $paidStartDate,
					        "paidEndDate" => $paidEndDate,
							));
							$lastid = $mydb->insert_id;

							$mydb->insert("transactions", array(
								"postID" => $lastid,
							));
						

						} //end else
						
					}//

				

				?>