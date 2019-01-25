<?php
namespace App\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Date;
use App\Entity\User;
use App\Entity\Post;
use App\Entity\comments;
// Si la sesion no esta iniciada la iniciamos
if(!isset($_SESSION)){ 
	session_start(); 
} 

/**
 * @Route("/")
 */
class DefaultController extends Controller {
   
	/**
	 * @Route("/", name="index")
	 */
	public function index(){
		$error_user_logged=""; $error_user_exist="";
        $entityManager = $this->getDoctrine()->getManager();
		
		// Si esta logeado mostramos todos los datos, sino vamos al login
		if (!empty($_SESSION['user_logged'])) {
			$repositoryPost = $this->getDoctrine()->getRepository(Post::class);	
			// Descargamos todos los y post
			$all_posts = $repositoryPost->findAll();
			return $this->render('index.html.twig', ['all_posts'=>$all_posts]);
		
		} else {
			return $this->render('session_form.html.twig', ['error_user_logged'=>$error_user_logged, 'error_user_exist'=>$error_user_exist]);		
		}
		
	}
	
	/**
     * @Route("/login_reg", name="login_reg")
	 * Metodo que comprueba si el usuario se ha logeado/registrado
	 */
	public function loginReg(){
        $repositoryUser = $this->getDoctrine()->getRepository(User::class);
		$entityManager = $this->getDoctrine()->getManager();
		$error_user_logged=""; $error_user_exist=""; $logged_ok=false;
		
		// Iniciar sesión
		if (isset($_POST['open_session'])) {
			// Tenemos usuario y contraseña
			if (!empty($_POST['user']) && !empty($_POST['password'])) {
		
				// buscamos el nombre de usuario en la base de datos
				if ($user_logged = $repositoryUser->findOneByName($_POST['user'])) {
					// la contraseña es igual a la pasada por el post
					if ($user_logged->getPassword() == $_POST['password']) {
						//si existe almacenamos sus datos en una session, levantamos la bandera
						$_SESSION['user_logged'] = $user_logged;
						$logged_ok = true;
					} else {
						$logged_ok = false;
					}
				} else {

					$logged_ok = false;
				}
			}

			// si no existe creamos un mensaje de error y nos vamos al formulario de inicio pasandole las variables de error
			if ($logged_ok == false) {
				$error_user_logged = "Usuario y/o contraseña incorrecto";
				return $this->render('session_form.html.twig', ['error_user_logged'=>$error_user_logged, 'error_user_exist'=>$error_user_exist]);		

				// si esta logeado ejecutamos otro metodo del controlador
			} else {
				return $this->redirectToRoute("index");

			}
		}        

		// Registrarse
		if (isset($_POST['registry'])) {
			$user_exist = false;
			
			if (!empty($_POST['user']) && !empty($_POST['password'])) {

				// buscamos el nombre de usuario en la base de datos
				if ($new_user = $repositoryUser->findOneByName($_POST['user'])) {
					//si existe levantamos la bandera
					$user_exist = true;
				} else {
					$user_exist = false;
				}			
			}

			// sino existe lo creamos, en caso contrario creamos un mensaje de error
			if ($user_exist==false) {
				$data_user['name']=$_POST['user'];
				$data_user['password']=$_POST['password'];

				$new_user = new User($data_user);

				// añadimos el usuario a la base de datos
				$entityManager->persist($new_user);
				$entityManager->flush();

				// Si el usuario ya existe creamos un error
			} else {
				$error_user_exist="El usuario introducido ya existe";
			}

			// volvemos al index pasando el error
			return $this->render('session_form.html.twig', ['error_user_logged'=>$error_user_logged, 'error_user_exist'=>$error_user_exist]);
		}
		
	}

	/**
     * @Route("/main_index", name="main_index")
	 * Metodo que recoge los datos del formulario de la cabecera
	 */
	public function mainIndex(){
		// Cerrar sesion
		if(isset($_POST['close_session'])){
			unset($_SESSION['user_logged']);
			return $this->redirectToRoute("index");
		}
		
		// Nuevo post. Mostramos el formualrio para crear un post
		if (isset($_POST['new_post'])) {
        	return $this->render('new_post_form.html.twig');
        	
        }     
           
       	// Modificar datos. Mostramos el formualrio para modificar los datos del usuario
        if (isset($_POST['modify_data'])) {
        	return $this->render('modify_data_form.html.twig', ['user_logged'=>$_SESSION['user_logged']]);
        	
        }

		// si acceden sin haber entrado mediante formualrio volverán al index
		return $this->redirectToRoute("index");
	}

	/**
	 * @Route("/create_new_comment", name="create_new_comment")
	 * Metodo para crear comentario
	 */
	public function createComment(){

		if(!empty($_POST['id_post']) && !empty($_POST['text_new_comment'])){
			$entityManager = $this->getDoctrine()->getManager();
			$repositoryPost = $this->getDoctrine()->getRepository(Post::class);			
			// Buscamos la id del post en la base de datos y descargamos el objeto
			if($post_comment = $repositoryPost->findOneById($_POST['id_post'])){
				// Creamos el array con los datos
				$datos['date'] = date("Y-m-d");
				$datos['user'] = $_SESSION['user_logged'];
				$datos['text'] = $_POST['text_new_comment'];
				$datos['post'] = $post_comment;

				// Creamos el objeto y lo subimos a la base de datos
				$new_comment = new Comments($datos);
				$entityManager->merge($new_comment);
				$entityManager->flush();
			}
		}
		// se haya creado o no el comentario volvemos al index
		return $this->redirectToRoute("index");
	}

	/**
	 * @Route("/create_post", name="create_post")
	 * Metodo para crear post
	 */
	public function createPost(){
		
		if (!empty($_POST['title_post']) && !empty($_POST['text_post'])) {
			$entityManager = $this->getDoctrine()->getManager();
			// creamos el objeto post y lo subimos a la base de datos
			$datos['user']=$_SESSION['user_logged'];
			$datos['title']=$_POST['title_post'];
			$datos['text']=$_POST['text_post'];
			$datos['publication_date'] = date("Y-m-d");;
			
			$new_post = new Post($datos);
			$entityManager->merge($new_post);
			$entityManager->flush();			
			
		}

		// Creado o no el post volvemos al index
		return $this->redirectToRoute("index");
	}

	/**
	 * @Route("/modify_data_user", name="modify_data_user")
	 * Metodo para modificar los datos del usuario
	 */
	public function modifyDataUser(){
		if (!empty($_POST['new_name_user']) && !empty($_POST['new_password_user'])) {
			$repositoryUser = $this->getDoctrine()->getRepository(User::class);			
			$entityManager = $this->getDoctrine()->getManager();
			// descargamos los datos del usuario logeado de la base de datos
			if ($user_logged = $repositoryUser->findOneByName($_SESSION['user_logged']->getName())) {
				//modificamos sus datos
				$user_logged->setName($_POST['new_name_user']);
				$user_logged->setPassword($_POST['new_password_user']);
				// modificamos la variable sesion con los datos recogidos
				$_SESSION['user_logged']=$user_logged;
				//subimos a bd
				$entityManager->persist($user_logged);
				$entityManager->flush();
			}
		}

		// Volvemos al index
		return $this->redirectToRoute("index");
	}

	/**
	 * @Route("/ver/{user}", name="ver_post")
	 * Metodo que lista los post de un usuario
	 */
	public function seePostUser($user){
        $repositoryUser = $this->getDoctrine()->getRepository(User::class);

		// buscamos el usuario en la base de datos
		if ($user_pass = $repositoryUser->findOneByName($user)) {
			// si existe sacamos los post del usuario en la vista
			return $this->render('post_from_user.html.twig', ['user_post'=>$user_pass->getPosts()]);		
		}

	}
	
}