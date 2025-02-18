<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/libraryConfig.php');
$dotenv = Dotenv\Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT']);
$dotenv->load();

/**
 * Class DataLayer
 * Handles database connections and data operations for the library application.
 */
class DataLayer
{
    /**
     * @var PDO $_dbh The database handle.
     */
    private $_dbh;

    /**
     * DataLayer constructor.
     * Initializes the database connection.
     */
    public function __construct() {
        // Require my PDO database connection credentials
        require_once($_SERVER['DOCUMENT_ROOT'].'/libraryConfig.php');

        try {
            // Instantiate our PDO Database Object
            $this->_dbh = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
            //echo 'Connected to database!';
        }
        catch(PDOException $e){
            //die($e->getMessage());
            die("<p>Something went wrong!</p>");
        }
    }

    /**
     * Gets the database handle.
     *
     * @return PDO The database handle.
     */
    public function getDbh() {
        return $this->_dbh;
    }

    /**
     * Retrieves search results from the Google Books API.
     *
     * @param string $searchTerm The search term to query.
     * @param string $printType The type of print (e.g., books, magazines).
     * @return JSON The decoded JSON response from the API.
     */
    public function getSearchResultsCurl($searchTerm, $printType)
    {
        $url = "https://www.googleapis.com/books/v1/volumes?q=" . $searchTerm . "&printType=" . $printType;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);

        return json_decode($output);
    }

    /**
     * Converts API items to objects (Books or Magazines).
     *
     * @param array $items The items to convert.
     * @return array An array of Book or Magazine objects.
     */
    public function getItemsAsObjects($items)
    {
        // Create an array to hold Item objects
        $itemObjects = array();

        if (sizeof($items) > 0){
            // Go through each item from the search results
            foreach ($items as $item) {

                // Set general Item params
                $itemParams = array();
                $itemParams['id'] = 1;
                $itemParams['title'] = $item->volumeInfo->title;
                $itemParams['desc'] = $item->volumeInfo->description;
                $itemParams['pubDate'] = $item->volumeInfo->publishedDate;
                $secondaryParams = array();

                // Create either a Book object or a Magazine object and add to the itemObject array
                if ($item->volumeInfo->printType == "BOOK") {
                    // Set Book params
                    $secondaryParams["authors"] = $item->volumeInfo->authors;
                    $secondaryParams["pages"] = $item->volumeInfo->pageCount;
                    $secondaryParams["isbn"] = $item->volumeInfo->industryIdentifiers[0]->identifier;
                    $secondaryParams["cover"] = $item->volumeInfo->imageLinks->thumbnail;

                    // Create a Book from all params
                    $book = new Book($itemParams, $secondaryParams);
                    $itemObjects[] = $book;

                } else if ($item->volumeInfo->printType == "MAGAZINE") {
                    // Set Magazine params
                    $secondaryParams["pages"] = $item->volumeInfo->pageCount;
                    $secondaryParams["cover"] = $item->volumeInfo->imageLinks->thumbnail;

                    // Create a Magazine from all params
                    $magazine = new Magazine($itemParams, $secondaryParams);
                    $itemObjects[] = $magazine;
                }
            }
        }
        return $itemObjects;
    }

    /**
     * Retrieves the items borrowed by a specific user.
     *
     * @param int $userId The ID of the user.
     * @return array An array of borrowed items.
     */
    public function getUserBorrowedItems($userId)
    {
        //get all users data
        $sql = "SELECT * FROM books WHERE user_id = :user_id";

        $statement = $this->_dbh->prepare($sql);

        $statement->bindParam(':user_id', $userId);
        $statement->execute();
        $items = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $items;
    }

    /**
     * Checks out an item.
     *
     * @return bool True on success, false on failure.
     */
    public function checkoutItem()
    {
        // 1. Define the query
        $sql = "INSERT INTO books (title, description, available, publishedDate, borrowedDate, returnDate,
                   user_id, author, pages, isbn, cover) VALUES (:title, :description, :available, :publishedDate,
                                                                :borrowedDate, :returnDate, :user_id, :authors,
                                                                :pages, :isbn, :cover)";

        // 2. Prepare the statement
        $statement = $this->_dbh->prepare($sql);

        // 3. Bind the parameters
        $title = $_POST['modal-item-title'] == "null" ? null : $_POST['modal-item-title'];
        $description = $_POST['modal-item-description'] == "null" ? null : substr($_POST['modal-item-description'], 0, 497).'...';
        $available = 0;
        $publishedDate = $_POST['modal-item-publishedDate'] == "null" ? null : $_POST['modal-item-publishedDate'];
        // Make sure date is in the correct format
        if (strlen($publishedDate) == 4){
            // Add Jan 1st if only year is available
            $publishedDate .= '-01-01';
        }else if (strlen($publishedDate) == 7){
            // Add the 1st if only year and month are available
            $publishedDate .= '-01';
        }
        $borrowedDate = date('Y-m-d', time());
        $returnDate = date('Y-m-d', strtotime($borrowedDate . '+14days'));
        $userId = $GLOBALS['f3']->get("SESSION.userId");
        $authors = $_POST['modal-item-authors'] == "null" ? null : $_POST['modal-item-authors'];
        $pages = $_POST['modal-item-pages'] == "null" ? null : $_POST['modal-item-pages'];
        $isbn = $_POST['modal-item-isbn'] == "null" ? null : $_POST['modal-item-isbn'];
        $cover = $_POST['modal-item-cover'] == "null" ? null : $_POST['modal-item-cover'];

        //echo $title . ", " . $description  . ", " . $available  . ", " . $publishedDate  . ", " . $borrowedDate  . ", " . $returnDate  . ", " . $userId  . ", " . $authors  . ", " . $pages  . ", " . $isbn  . ", " . $cover;

        $statement->bindParam(':title', $title);
        $statement->bindParam(':description', $description);
        $statement->bindParam(':available', $available);
        $statement->bindParam(':publishedDate', $publishedDate);
        $statement->bindParam(':borrowedDate', $borrowedDate);
        $statement->bindParam(':returnDate', $returnDate);
        $statement->bindParam(':user_id', $userId);
        $statement->bindParam(':authors', $authors);
        $statement->bindParam(':pages', $pages);
        $statement->bindParam(':isbn', $isbn);
        $statement->bindParam(':cover', $cover);


        // 4. Execute the query
        try {
            $successful = $statement->execute();
            if ($successful){
                return true;
            }else{
                return false;
            }
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Returns an item.
     *
     * @param int $bookId The ID of the book to return.
     * @param int $userId The ID of the user returning the book.
     * @return bool True on success, false on failure.
     */
    public function returnItem($bookId, $userId)
    {
        $newId = null;
        $available = true;

        // 1. Define the query
        $sql = "UPDATE books SET available=:available, user_id=:new_id WHERE id=:id && user_id=:old_id";

        // 2. Prepare the statement
        $statement = $this->_dbh->prepare($sql);

        $statement->bindParam(':available', $available, PDO::PARAM_INT);
        $statement->bindParam(':id', $bookId);
        $statement->bindParam(':new_id', $newId);
        $statement->bindParam(':old_id', $userId);

        echo $bookId . " - " . $userId . " - " . $newId . " - " . $available;

        echo $statement->queryString;

        // 4. Execute the query
        try {
            $successful = $statement->execute();
            return $successful;
        } catch (\PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Sends an overdue email to users with overdue items.
     *
     * @return bool True if all emails were sent successfully, false otherwise.
     */
    public function sendOverdueEmail()
    {

        $sql = "SELECT books.*, users.* FROM books JOIN users ON books.user_id = users.id WHERE books.returnDate < :today;";
        $statement = $this->_dbh->prepare($sql);

        $today = date('Y/m/d');
        $statement->bindParam(':today', $today);
        $statement->execute();
        $users = $statement->fetchAll(PDO::FETCH_ASSOC);

        $completed = true;

        if ($users){

            foreach($users as $user){
                $overdueItem = $_POST['overdueItem'];

                $firstName = $user["fName"];
                $lastName = $user["lName"];
                $email = $user["email"];
                $message = "This is just a friendly reminder that " . $overdueItem . " is overdue. Please return the 
                                                                               item at your earliest convenience.";

                $to = 'miss.matthew@student.greenriver.edu';
                $subject = 'Contact Form Submission';
                $body = "First Name: $firstName\nLast Name: $lastName\nEmail: $email\nMessage: $message";
                $headers = "From: $email";

                // Send email to user
                if (mail($to, $subject, $body, $headers) == false) {
                    $completed = false;
                }
            }

        }
        return $completed;
    }

    /**
     * Checks if a return date is overdue.
     *
     * @param string $returnDate The return date of the item.
     * @return bool True if the return date is passed todays date, false otherwise.
     */
    public static function isOverdue($returnDate)
    {
        $date = strtotime($returnDate);
        return ceil(($date - time()) / 60 / 60 / 24) < 0;
    }

    /**
     * Gets the number of days from the return date.
     *
     * @param string $returnDate The return date of the item.
     * @return int The number of days from the return date.
     */
    public static function getDaysFromReturnDate($returnDate)
    {
        $date = strtotime($returnDate);
        return abs(ceil(($date - time()) / 60 / 60 / 24)); // Absolute value used so no negatives returned
    }

    /**
     * Creates a Stripe checkout session.
     *
     * @param int $amount The amount to be charged.
     * @param string $success_url The URL to redirect to on success.
     * @param string $cancel_url The URL to redirect to on cancellation.
     * @return string The URL of the checkout session.
     */
    public function createCheckoutSession($amount, $success_url, $cancel_url)
    {
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Donation',
                    ],
                    'unit_amount' => $amount * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
        ]);

        return $session->url;
    }

    /**
     * Handles the success of a Stripe checkout session.
     *
     * @param string $sessionId The ID of the Stripe session.
     * @param object $f3 The F3 framework instance.
     * @throws Exception If the donation cannot be recorded.
     */
    public function handleSuccess($sessionId, $f3)
    {
        require_once($_SERVER['DOCUMENT_ROOT'].'/libraryConfig.php');
        \Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        if ($f3->exists('SESSION.userId')) {
            $userId = $f3->get('SESSION.userId');
            $amount = $session->amount_total / 100;

            $dbh = $this->getDbh();
            $sql = 'INSERT INTO donations (user_id, amount) VALUES (:user_id, :amount)';
            $statement = $dbh->prepare($sql);
            $statement->bindParam(':user_id', $userId);
            $statement->bindParam(':amount', $amount);

            if (!$statement->execute()) {
                throw new Exception('Failed to record the donation.');
            }

            // Get user information
            $sql = "SELECT first, last, email FROM users WHERE id = :id";
            $statement = $this->_dbh->prepare($sql);
            $statement->bindParam(':id', $userId);
            $statement->execute();
            $userInfo = $statement->fetch(PDO::FETCH_ASSOC);

            // Set donation and user info to F3 session
            $f3->set('SESSION.donationAmount', $amount);
            $f3->set('SESSION.donationDate', date('Y-m-d H:i:s'));
            $f3->set('SESSION.userInfo', $userInfo);
        }

        $f3->set('message', 'Donation successful! Thank you for your contribution.');
    }

    /**
     * Retrieves the donation leaderboard.
     *
     * @return array The leaderboard data.
     */
    public function getLeaderboard()
    {
        $sql = "SELECT users.first, users.last, SUM(donations.amount) as total_amount
                FROM donations
                JOIN users ON donations.user_id = users.id
                GROUP BY donations.user_id
                ORDER BY total_amount DESC";
        $statement = $this->_dbh->prepare($sql);
        $statement->execute();
        $leaderboard = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Calculate rank
        foreach ($leaderboard as $index => &$donor) {
            $donor['rank'] = $index + 1;
        }

        return $leaderboard;
    }

    /**
     * Retrieves all users.
     *
     * @return array An array of user data.
     */
    public function getUsers()
    {
        //get all users data
        $sql = "SELECT * FROM users";
        $statement = $this->_dbh->prepare($sql);
        $statement->execute();
        $users = $statement->fetchAll(PDO::FETCH_ASSOC);
        return $users;
    }

    /**
     * Retrieves all borrowed items.
     *
     * @return array An array of borrowed items.
     */
    public function getAllBorrowedItems(){
        $available = false;

        //get all books data
        $sql = "SELECT * FROM books WHERE available = :available ORDER BY returnDate";
        $statement = $this->_dbh->prepare($sql);
        $statement->bindParam(':available', $available, PDO::PARAM_INT);
        $statement->execute();
        $books = $statement->fetchAll(PDO::FETCH_ASSOC);

        $itemObjects = array();

        foreach ($books as $book){
            // Set General Item Parameters
            $itemParams = array();
            $itemParams['id'] = $book['id'];
            $itemParams['title'] = $book['title'];
            $itemParams['desc'] = $book['description'];
            $itemParams['pubDate'] = $book['publishedDate'];
            $itemParams['available'] = $book['available'];
            $itemParams['borrowDate'] = $book['borrowedDate'];
            $itemParams['returnDate'] = $book['returnDate'];
            $itemParams['borrower'] = $book['user_id'];

            // Set Book Parameters
            $secondaryParams = array();
            $secondaryParams['authors'] = $book['authors'];
            $secondaryParams['pages'] = $book['pages'];
            $secondaryParams['isbn'] = $book['isbn'];
            $secondaryParams['cover'] = $book['cover'];

            // Instantiate a Book and add to the item array
            $bookObj = new Book($itemParams, $secondaryParams);
            $itemObjects[] = $bookObj;
        }

        //get all books data
        $sql = "SELECT * FROM magazines WHERE available = :available ORDER BY returnDate";
        $statement = $this->_dbh->prepare($sql);
        $statement->bindParam(':available', $available, PDO::PARAM_INT);
        $statement->execute();
        $mags = $statement->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mags as $mag){
            // Set General Item Parameters
            $itemParams = array();
            $itemParams['id'] = $mag['id'];
            $itemParams['title'] = $mag['title'];
            $itemParams['desc'] = $mag['description'];
            $itemParams['pubDate'] = $mag['publishedDate'];
            $itemParams['available'] = $mag['available'];
            $itemParams['borrowDate'] = $mag['borrowedDate'];
            $itemParams['returnDate'] = $mag['returnDate'];
            $itemParams['borrower'] = $mag['user_id'];

            // Set Book Parameters
            $secondaryParams = array();
            $secondaryParams['pages'] = $mag['pages'];
            $secondaryParams['cover'] = $mag['cover'];

            // Instantiate a Book and add to the item array
            $magObj = new Magazine($itemParams, $secondaryParams);
            $itemObjects[] = $magObj;
        }

        // Sort all items by the returnDate
        usort($itemObjects, fn($a, $b) => $a->getReturnDate()<=> $b->getReturnDate());

        return $itemObjects;
    }

    /**
     * Retrieves user credentials based on email.
     *
     * @param string $email The email of the user.
     * @return array|bool The user credentials or false if not found.
     */
    public function getCredentials($email)
    {
        // get user information from database
        $sql = 'SELECT * FROM users WHERE `email`= :email';
        $statement = $this->_dbh->prepare($sql);
        $statement->bindParam(':email', $email);
        $statement->execute();
        echo '<script>console.log("statement executed");</script>';

        // return results
        if ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            return $row;
        } else {
            return false;
        }
    }

    /**
     * Adds a new user to the database.
     *
     * @param string $firstName The first name of the user.
     * @param string $lastName The last name of the user.
     * @param string $email The email of the user.
     * @param string $passwordHash The hashed password of the user.
     */
    public function addUser($firstName, $lastName, $email, $passwordHash)
    {
        $sql = 'INSERT INTO users (first, last, email, password) VALUES (:first, :last, :email, :password)';
        $statement = $this->_dbh->prepare($sql);
        $statement->bindParam(':first', $firstName);
        $statement->bindParam(':last', $lastName);
        $statement->bindParam(':email', $email);
        $statement->bindParam(':password', $passwordHash);
        $statement->execute();
    }

    /**
     * Gets the last inserted ID.
     *
     * @return string The last inserted ID.
     */
    public function getLastInsertId()
    {
        return $this->_dbh->lastInsertId();
    }

}
