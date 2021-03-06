<?php

	require_once( "../../server/bootstrap.php" );

	if(!isset($_POST["metodo"])){
		die(json_encode(array("status" => "error" , "resason" => "must send metodo to call")));
	}
  
	function doQuery($sql){
		if(!mysql_query($sql) ){
			die(json_encode(array("status" => "error", "reason" => "Error num: ".  mysql_errno().": ".mysql_error() )));
		}
		
		die(json_encode(array("status" => "ok")));
	}



	switch($_POST["metodo"])
	{
		case "editarPaquete" :
			
			//primero vamos a ver si se puede editar
			$islocked = mysql_fetch_assoc(mysql_query("select locked from httptesting_paquete_de_pruebas where id_paquete_de_pruebas = ". $_POST["id_paquete_de_pruebas"] .";"));
			if($islocked["locked"]){
				die(json_encode(array("status" => "error", "reason" => "este paquete tiene lock" )));
			}
			
			if(!isset($_POST["locked"]))
				$locked = 0;
			else 
				$locked = $_POST["locked"];
			
			$p = addslashes($_POST["pruebas"]);
			$sql = "Update httptesting_paquete_de_pruebas set pruebas = '".$p."', nombre='".$_POST["nombre"]."', descripcion='".$_POST["descripcion"]."', locked=".$locked." where id_paquete_de_pruebas=".$_POST["id_paquete_de_pruebas"];
			doQuery($sql);
	    break;
			
		case "nuevoPaquete" :
			$sql = "Insert into httptesting_paquete_de_pruebas(pruebas,nombre,descripcion,locked,id_proyecto) values('".$_POST["pruebas"]."','".$_POST["nombre"]."','".$_POST["descripcion"]."',".$_POST["locked"].",".$_POST["proyecto"].")";
			doQuery($sql);
		break;
			
		case "nuevaRuta" :
			$sql = "Insert into httptesting_ruta(nombre,ruta,id_proyecto) values('".$_POST["nombre"]."','".$_POST["ruta"]."',".$_POST["proyecto"].")";
			doQuery($sql);			
		break;
			
		case "editarRuta" :
			$sql = "Update httptesting_ruta set nombre = '".$_POST["nombre"]."', ruta = '".$_POST["ruta"]."' where id_ruta = ".$_POST["id_ruta"];
			doQuery($sql);			
		break;
		
		case "test":

			#########################################################
			## parse tests
			#########################################################
			$Paquete = mysql_fetch_assoc(mysql_query("SELECT * FROM httptesting_paquete_de_pruebas WHERE id_paquete_de_pruebas = " . $_POST["test_id"] . ";"));
			$Url = mysql_fetch_assoc(mysql_query("SELECT * FROM httptesting_ruta where id_ruta = " . $_POST["url"] . ";"));
			
			$tparser = new TestParser( $Paquete["pruebas"] );

			try{
				$tparser->parse();

			}catch(Exception $e){
				die(json_encode(array("status" => "error", "reason" => $e )));

			}

			#########################################################
			## bit of configuration
			#########################################################	
			HTTPClient::setUrlBase( $Url["ruta"] );



			#########################################################
			## start testing
			#########################################################
			$output = array();
			while($tparser->hasNextTest())
			{

				$tester = new Tester( $tparser->nextTest() );

				try{
					$tester->test();

				}catch(Exception $e){
					die(json_encode(array("status" => "error", "reason" => $e )));
				}

			}


		break;
		
		
	}
	

