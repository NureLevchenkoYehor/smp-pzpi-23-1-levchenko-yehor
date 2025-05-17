#!/usr/bin/env php
<?php
class StoreItem implements JsonSerializable {
    private string $name;
    private int $id;
    private int $price;

    public function __construct(string $name, int $id, int $price) {
        $this->id = $id;
        $this->price = $price;
        $this->name = $name;
    }
    
    public function get_name() : string {
        return $this->name;
    }

    public function get_id() : int {
        return $this->id;
    }
    
    public function get_price() : int {
        return $this->price;
    }

    public function jsonSerialize() : array {
        return [
            "name" => $this->get_name(),
            "id" => $this->get_id(),
            "price" => $this->get_price()
        ];
    }
}

class CartItem implements JsonSerializable {
    private int $id;
    private int $amount;

    public function __construct(int $id, int $amount) {
        $this->id = $id;
        $this->amount = $amount;
    }

    public function get_id() : int {
        return $this->id;
    }

    public function get_amount() : int {
        return $this->amount;
    }

    public function set_amount(int $amount) : void {
        $this->amount = $amount;
    }

    public function jsonSerialize() : array {
        return [
            "id" => $this->get_id(),
            "amount" => $this->get_amount()
        ];
    }
}

class Inventory implements JsonSerializable {
    private array $items;

    public function __construct(array $items = []) {
        $this->items = [];
        foreach ($items as $item) {
            $this->items[$item->get_id()] = $item;
        }
    }

    public function get(int $id) : StoreItem {
        if ($this->exists($id) === FALSE) {
            echo "Item do not exists.\n";
            // TODO: Implement ERROR
            return NULL;
        }
        return $this->items[$id];
    }

    public function get_all() : array {
        return $this->items;
    }

    public function count() : int {
        return count($this->items);
    }
    
    public function add(StoreItem $item) : void {
        $id = $item->get_id();
        if ($this->exists($id) === TRUE) {
            echo "Item already exists.\n";
            // TODO: Implement ERROR
            return;
        }
        $this->items[$id] = $item;
    }

    public function exists(int $id) : bool {
        return key_exists($id, $this->items);
    }

    public function update(StoreItem $item) : void {
        $id = $item->get_id();
        if ($this->exists($id) === FALSE) {
            echo "Item do not exists.\n";
            // TODO: Implement ERROR
            return;
        }
        $this->items[$id] = $item;
    }

    public function remove(int $id) : void {
        if ($this->exists($id) === FALSE) {
            echo "Item do not exists.\n";
            // TODO: Implement for acceptable scenario
            return;
        }
        unset($this->items[$id]);
    }

    public function jsonSerialize() : array {
        return ["items" => array_values($this->get_all())];
    }
}

class Profile implements JsonSerializable {
    private ?string $name;
    private ?int $age;

    public function __construct(?string $name = NULL, ?int $age = NULL) {
        $this->name = $name;
        $this->age = $age;
    }

    public function get_name() : ?string {
        return $this->name;
    }

    public function get_age() : ?int {
        return $this->age;
    }

    public function update($name, $age) : void {
        $this->name = $name;
        $this->age = $age;
    }
    
    public function jsonSerialize() : array {
        return [
            "name" => $this->get_name(),
            "age" => $this->get_age()
        ];
    }
}

class Cart implements JsonSerializable {
    private array $items;

    public function __construct(array $items = []) {
        $this->items = [];
        foreach ($items as $item) {
            $this->items[$item->get_id()] = $item;
        }
    }

    public function get(int $id) : StoreItem {
        if ($this->exists($id) === FALSE) {
            echo "Item do not exists.\n";
            // TODO: Implement ERROR
            return NULL;
        }
        return $this->items[$id];
    }

    public function get_all() : array {
        return $this->items;
    }

    public function count() : int {
        return count($this->items);
    }

    public function exists(int $id) : bool {
        return key_exists($id, $this->items);
    }
    
    public function add(CartItem $item) : void {
        $id = $item->get_id();
        if ($this->exists($id) === TRUE) {
            echo "Item already exists.\n";
            // TODO: Implement ERROR
            return;
        }
        $this->items[$id] = $item;
    }

    public function update(int $id, int $amount) : void {
        if ($this->exists($id) === FALSE) {
            echo "Item do not exists.\n";
            // TODO: Implement ERROR
            return;
        }
        $this->items[$id]->set_amount($amount);
    }

    public function remove(int $id) : void {
        if ($this->exists($id) === FALSE) {
            echo "Item do not exists.\n";
            // TODO: Implement for acceptable scenario
            return;
        }
        unset($this->items[$id]);
    }

    public function jsonSerialize() : array {
        return ["items" => array_values($this->get_all())];
    }
}

class Store implements JsonSerializable {
    private string $name;
    private Inventory $inventory;

    public function __construct(string $name, Inventory $inventory) {
        $this->name = $name;
        $this->inventory = $inventory;
    }

    public function get_name() : string {
        return $this->name;
    }

    public function get_inventory() : Inventory {
        return $this->inventory;
    }

    public function jsonSerialize() : array {
        return [
            "name" => $this->get_name(),
            "inventory" => $this->get_inventory()
        ];
    }
}

class User implements JsonSerializable {
    private Cart $cart;
    private Profile $profile;

    public function __construct(Cart $cart, Profile $profile) {
        $this->cart = $cart;
        $this->profile = $profile;
    }

    public function get_cart() : Cart {
        return $this->cart;
    }
    
    public function get_profile() : Profile {
        return $this->profile;
    }

    public function jsonSerialize() : array {
        return [
            "cart" => $this->get_cart(),
            "profile" => $this->get_profile()
        ];
    }
}

class DataHandler {
    private function __construct() {
        // Prevent instantiation
    }
    
    public static function fetch_store_data(string $store_data_filepath) : ?Store {
        $store_data = self::fetch_json($store_data_filepath);
        if ($store_data === NULL) {
            // TODO: Handle error
            return NULL;
        }
        $store_data->items = array_map(
            fn ($o) => new StoreItem($o->name, $o->id, $o->price), 
            $store_data->items
        );
        $inventory = new Inventory($store_data->items);
        $store = new Store($store_data->name, $inventory);
        return $store;
    }

    public static function fetch_user_data(string $user_data_filepath, bool $include_cart = false) : ?User {
        $user_data = self::fetch_json($user_data_filepath);
        if ($user_data === NULL) {
            // TODO: Handle error
            return NULL;
        }
        if ($include_cart === TRUE) {
            $user_data->cart->items = array_map(
                fn($o) => new CartItem($o->id, $o->amount), 
                $user_data->cart->items
            );
            $cart = new Cart($user_data->cart->items);
        } else {
            $cart = new Cart();
        }
        $profile = new Profile($user_data->profile->name, $user_data->profile->age);
        $user = new User($cart, $profile);
        return $user;
    }

    private static function fetch_json(string $filename) : mixed {
        $file_content = file_get_contents($filename);
        if ($file_content === FALSE) {
            // TODO: Handle error
            return NULL;
        }
        $data = json_decode($file_content);
        if ($data === NULL) {
            // TODO: Handle error
            return NULL;
        }
        return $data;
    }

    public static function save_data(string $filename, mixed $data) : void {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($filename, $json);
    }
}

enum ProgramState {
    case MAIN_MENU;
    case ITEMS_MENU;
    case BILL_MENU;
    case PROFILE_MENU;
    case SHUTDOWN;
}

class MainMenu {
    private const LABEL = '
################################
# ПРОДОВОЛЬЧИЙ МАГАЗИН "ВЕСНА" #
################################';
    private const PROMPT = 'Введіть команду: ';
    private const WRONG_COMMAND_ERROR_MESSAGE = 'ПОМИЛКА! Введіть правильну команду';
    private const OPTIONS = [
        1 => 'Вибрати товари',
        2 => 'Отримати підсумковий рахунок',
        3 => 'Налаштувати свій профіль',
        0 => 'Вийти з програми'
    ];

    private function __construct() {
        // Prevent instantiation
    }

    public static function open(bool $show_label = FALSE) : ProgramState {
        if ($show_label === TRUE) {
            echo MainMenu::LABEL;
        }
        foreach (MainMenu::OPTIONS as $num => $option) {
            echo "$num $option\n";
        }
        $choice = (int)readline(MainMenu::PROMPT);
        if ($choice < 0 || $choice > 3) {
            echo MainMenu::WRONG_COMMAND_ERROR_MESSAGE . "\n";
        }
        return match ($choice) {
            1 => ProgramState::ITEMS_MENU,
            2 => ProgramState::BILL_MENU,
            3 => ProgramState::PROFILE_MENU,
            0 => ProgramState::SHUTDOWN,
            default => ProgramState::MAIN_MENU
        };
    }
}

class ItemsMenu {
    private const SEPARATOR = '   -----------';
    private const STORE_ITEMS_COLUMNS = ['№', 'НАЗВА', 'ЦІНА'];
    private const PRODUCT_SELECT_PROMPT = 'Виберіть товар: ';
    private const AMOUNT_SELECT_PROMPT = 'Введіть кількість, штук: ';
    private const SELECTED_LABEL = 'Вибрано: ';
    private const EXIT_OPTION = '0  ПОВЕРНУТИСЯ';
    private const PRODUCT_REMOVAL_MESSAGE = 'ВИДАЛЯЮ ТОВАР З КОШИКА';
    private const CART_EMPTY_MESSAGE = 'КОШИК ПОРОЖНІЙ';
    private const CART_LABEL = 'У КОШИКУ:';
    private const CART_ITEMS_COLUMNS = ['НАЗВА', 'КІЛЬКІСТЬ'];
    private const WRONG_ITEM_NUMBER_ERROR_MESSAGE = 'ПОМИЛКА! Ви вказали неправильний номер товару';
    private const WRONG_AMOUNT_ERROR_MESSAGE = 'ПОМИЛКА! Ви вказали неправильну кількість товару';

    private function __construct() {
        // Prevent instantiation
    }

    public static function open(Cart $cart, Inventory $inventory) : ProgramState {
        self::print_items($inventory);
        echo self::SEPARATOR . "\n" . self::EXIT_OPTION . "\n";
        $choice = (int)readline(self::PRODUCT_SELECT_PROMPT);
        if ($choice < 0 || $choice > $inventory->count()) {
            echo self::WRONG_ITEM_NUMBER_ERROR_MESSAGE . "\n\n";
            return ProgramState::ITEMS_MENU;
        }
        if ($choice === 0) {
            return ProgramState::MAIN_MENU;
        }
        $items = array_values($inventory->get_all());
        // TODO
        $item = $items[$choice - 1];
        echo self::SELECTED_LABEL . $item->get_name() . "\n";
        $amount = (int)readline(self::AMOUNT_SELECT_PROMPT);
        if ($amount < 0) {
            echo self::WRONG_AMOUNT_ERROR_MESSAGE . "\n";
            return ProgramState::ITEMS_MENU;
        }
        $id = $item->get_id();
        $exists = $cart->exists($id) === TRUE;
        if ($amount === 0 && $exists === TRUE) {
            echo self::PRODUCT_REMOVAL_MESSAGE . "\n";
            $cart->remove($id);
        } else if ($exists === TRUE) {
            $cart->update($id, $amount);
        } else {
            $cart->add(new CartItem($id, $amount));
        }
        self::print_cart($cart, $inventory);
        return ProgramState::ITEMS_MENU;
    }

    private static function print_items(Inventory $inventory) : void {
        $rows = [];
        $i = 1;
        foreach ($inventory->get_all() as $item) {
            $row = [$i, $item->get_name(), $item->get_price()];
            array_push($rows, $row);
            $i++;
        }
        self::print_table(array_merge([self::STORE_ITEMS_COLUMNS], $rows));
    }

    private static function print_cart(Cart $cart, Inventory $inventory) : void {
        if ($cart->count() === 0) {
            echo self::CART_EMPTY_MESSAGE . "\n";
        }
        echo self::CART_LABEL . "\n";
        $rows = [];
        foreach ($cart->get_all() as $id => $item) {
            $item_name = $inventory->get($id)->get_name();
            $row = [$item_name, $item->get_amount()];
            array_push($rows, $row);
        }
        self::print_table(array_merge([self::CART_ITEMS_COLUMNS], $rows));
        echo "\n";
    }

    private static function print_table(array $table, string $separator = '  ') : void {
        $column_widths = self::get_column_widths($table);
        $rows_count = count($table);
        $columns_count = count($table[0]);
        foreach ($table as $i => $row) {
            foreach ($row as $j => $field) {
                $column_width = $column_widths[$j];
                $remainder = $column_width - mb_strlen($field);
                echo $field . str_repeat(' ', $remainder);
                if ($j !== $columns_count) {
                    echo $separator;
                }
            }
            if ($i !== $rows_count) {
                echo "\n";
            }
        }
    }

    private static function get_column_widths(array $table) : array {
        $column_widths = [];
        foreach ($table as $row) {
            foreach ($row as $i => $value) {
                if (key_exists($i, $column_widths) === FALSE) {
                    $column_widths[$i] = mb_strlen($value);
                    continue;
                }
                $column_widths[$i] = max(mb_strlen($value), $column_widths[$i]);
            }
        }
        return $column_widths;
    }
}

class BillMenu {
    private const CART_EMPTY_MESSAGE = 'КОШИК ПОРОЖНІЙ';
    private const TOTAL_MESSAGE = 'РАЗОМ ДО CПЛАТИ: ';
    private const COLUMNS = ['№', 'НАЗВА', 'ЦІНА', 'КІЛЬКІСТЬ', 'ВАРТІСТЬ'];

    private function __construct() {
        // Prevent instantiation
    }

    public static function open(Cart $cart, Inventory $inventory) : ProgramState {
        if ($cart->count() === 0) {
            echo self::CART_EMPTY_MESSAGE . "\n\n";
            return ProgramState::MAIN_MENU;
        }
        self::print_bill($cart, $inventory);
        return ProgramState::MAIN_MENU;
    }

    private static function print_bill(Cart $cart, Inventory $inventory) : void {
        $rows = [];
        $total = 0;
        $i = 1;
        foreach ($cart->get_all() as $id => $item) {
            $item_info = $inventory->get($id);
            $price = $item_info->get_price();
            $amount = $item->get_amount();
            $cost = $price * $amount;
            $total += $cost;
            $row = [$i, $item_info->get_name(), $amount, $price, $cost];
            array_push($rows, $row);
            $i++;
        }
        $table = array_merge([self::COLUMNS], $rows);
        self::print_table($table);
        echo self::TOTAL_MESSAGE . $total . "\n\n";
    }

    private static function print_table(array $table , string $separator = '  ') : void {
        $column_widths = self::get_column_widths($table);
        $rows_count = count($table);
        $columns_count = count(self::COLUMNS);
        foreach ($table as $i => $row) {
            foreach ($row as $j => $field) {
                $column_width = $column_widths[$j];
                $remainder = $column_width - mb_strlen($field);
                echo $field . str_repeat(' ', $remainder);
                if ($j !== $columns_count) {
                    echo $separator;
                }
            }
            if ($i !== $rows_count) {
                echo "\n";
            }
        }
    }

    private static function get_column_widths(array $table) : array {
        $column_widths = [];
        foreach ($table as $row) {
            foreach ($row as $i => $value) {
                if (key_exists($i, $column_widths) === FALSE) {
                    $column_widths[$i] = mb_strlen($value);
                    continue;
                }
                $column_widths[$i] = max(mb_strlen($value), $column_widths[$i]);
            }
        }
        return $column_widths;
    }
}

class ProfileSetupMenu {
    private const NAME_PROMPT = 'Ваше імʼя: ';
    private const AGE_PROMPT = 'Ваш вік: ';
    private const AGE_OUT_OF_RANGE_ERROR_MESSAGE = 'ПОМИЛКА! Користувач повинен мати вік від 7 та до 150 років';
    private const AGE_NAN_ERROR_MESSAGE = 'ПОМИЛКА! Вік користувача потрібно вказати числом';
    
    private function __construct() {
        // Prevent instantiation
    }

    public static function open(User $user, $user_data_filepath) : ProgramState {
        $name = self::get_name();
        $age = self::get_age();
        echo "\n";
        $user->get_profile()->update($name, $age);
        DataHandler::save_data($user_data_filepath, $user);
        return ProgramState::MAIN_MENU;
    }

    private static function get_name() : string {
        while ($name = readline(self::NAME_PROMPT) === '');
        return $name;
    }

    private static function get_age() : int {
        while (self::validate_age($age = readline(self::AGE_PROMPT)) === FALSE);
        return (int)$age;
    }

    private static function validate_age(string $age) : bool {
        if (filter_var($age, FILTER_VALIDATE_INT) === FALSE) {
            echo self::AGE_NAN_ERROR_MESSAGE . "\n\n";
            return FALSE;
        }
        if ($age < 7 || $age > 150) {
            echo self::AGE_OUT_OF_RANGE_ERROR_MESSAGE . "\n\n";
            return FALSE;
        }
        return TRUE;
    }
}

class Program {
    private const WRONG_STATE_ERROR = 228;

    public static function main(string ...$args) : int {
        $store_data_filepath = 'data/store_data.json';
        $store = DataHandler::fetch_store_data($store_data_filepath);
        
        $user_data_filepath = 'data/user_data.json';
        $user = DataHandler::fetch_user_data($user_data_filepath);
        // Will only work if the error is related to file missing
        if ($user === NULL) {
            $user = new User(new Cart(), new Profile());
            DataHandler::save_data($user_data_filepath, $user);
        }

        // Do things
        $state = ProgramState::MAIN_MENU;
        while ($state !== ProgramState::SHUTDOWN) {
            switch ($state) {
                case ProgramState::MAIN_MENU:
                    $state = MainMenu::open();
                    break;
                case ProgramState::ITEMS_MENU:
                    $state = ItemsMenu::open($user->get_cart(), $store->get_inventory());
                    break;
                case ProgramState::BILL_MENU:
                    $state = BillMenu::open($user->get_cart(), $store->get_inventory());
                    break;
                case ProgramState::PROFILE_MENU:
                    $state = ProfileSetupMenu::open($user, $user_data_filepath);
                    break;
                default:
                    // TODO: Handle Error
                    return self::WRONG_STATE_ERROR;
            }
        }
        return 0;
    }
}

Program::main();
