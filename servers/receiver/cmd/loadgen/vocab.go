package main

// What an ordinary Laravel application sends: the routes, queries, classes and messages
// the generated traces are made of. Only shapes matter here, the values are invented.

type route struct {
	method     string
	uri        string
	pattern    string
	action     string
	withItems  bool
	medianMs   float64
	failedRate float64
}

var routes = []route{
	{"GET", "/api/products", "/api/products", "App\\Http\\Controllers\\ProductController@index", false, 45, 0.005},
	{"GET", "/api/products/%d", "/api/products/{product}", "App\\Http\\Controllers\\ProductController@show", false, 25, 0.01},
	{"GET", "/api/categories", "/api/categories", "App\\Http\\Controllers\\CategoryController@index", false, 20, 0.002},
	{"GET", "/api/search", "/api/search", "App\\Http\\Controllers\\SearchController@index", false, 180, 0.02},
	{"POST", "/api/cart/items", "/api/cart/items", "App\\Http\\Controllers\\CartItemController@store", true, 60, 0.01},
	{"DELETE", "/api/cart/items/%d", "/api/cart/items/{item}", "App\\Http\\Controllers\\CartItemController@destroy", false, 35, 0.005},
	{"GET", "/api/cart", "/api/cart", "App\\Http\\Controllers\\CartController@show", false, 30, 0.003},
	{"POST", "/api/orders", "/api/orders", "App\\Http\\Controllers\\OrderController@store", true, 320, 0.03},
	{"GET", "/api/orders", "/api/orders", "App\\Http\\Controllers\\OrderController@index", false, 70, 0.005},
	{"GET", "/api/orders/%d", "/api/orders/{order}", "App\\Http\\Controllers\\OrderController@show", true, 40, 0.01},
	{"POST", "/api/orders/%d/pay", "/api/orders/{order}/pay", "App\\Http\\Controllers\\OrderPaymentController@store", false, 850, 0.06},
	{"POST", "/api/auth/login", "/api/auth/login", "App\\Http\\Controllers\\Auth\\LoginController@store", false, 150, 0.08},
	{"POST", "/api/auth/logout", "/api/auth/logout", "App\\Http\\Controllers\\Auth\\LoginController@destroy", false, 15, 0.001},
	{"GET", "/api/profile", "/api/profile", "App\\Http\\Controllers\\ProfileController@show", false, 20, 0.002},
	{"PUT", "/api/profile", "/api/profile", "App\\Http\\Controllers\\ProfileController@update", false, 55, 0.02},
	{"GET", "/api/notifications", "/api/notifications", "App\\Http\\Controllers\\NotificationController@index", false, 35, 0.004},
	{"POST", "/api/webhooks/payments", "/api/webhooks/payments", "App\\Http\\Controllers\\Webhooks\\PaymentWebhookController", false, 95, 0.015},
	{"GET", "/admin/reports/sales", "/admin/reports/sales", "App\\Http\\Controllers\\Admin\\SalesReportController@index", false, 1400, 0.04},
	{"GET", "/admin/users", "/admin/users", "App\\Http\\Controllers\\Admin\\UserController@index", false, 110, 0.005},
	{"POST", "/admin/products/import", "/admin/products/import", "App\\Http\\Controllers\\Admin\\ProductImportController@store", true, 2500, 0.07},
	{"GET", "/health", "/health", "App\\Http\\Controllers\\HealthController", false, 3, 0.001},
}

type query struct {
	sql      string
	tables   string
	medianMs float64
	bindings int
}

var queries = []query{
	{"select * from `users` where `id` = ? limit 1", "users", 0.4, 1},
	{"select * from `personal_access_tokens` where `token` = ? limit 1", "tokens", 0.5, 1},
	{"select * from `products` where `is_active` = ? order by `created_at` desc limit 20 offset ?", "products", 3.5, 2},
	{"select * from `products` where `products`.`id` = ? limit 1", "products", 0.4, 1},
	{"select count(*) as aggregate from `products` where `is_active` = ?", "products", 6, 1},
	{"select * from `categories` where `parent_id` is null order by `position` asc", "categories", 0.8, 0},
	{"select * from `product_images` where `product_images`.`product_id` in (?, ?, ?, ?)", "product_images", 1.2, 4},
	{"select * from `carts` where `user_id` = ? and `status` = ? limit 1", "carts", 0.6, 2},
	{"insert into `cart_items` (`cart_id`, `product_id`, `qty`, `price`, `updated_at`, `created_at`) values (?, ?, ?, ?, ?, ?)", "cart_items", 1.8, 6},
	{"update `carts` set `total` = ?, `carts`.`updated_at` = ? where `id` = ?", "carts", 1.5, 3},
	{"insert into `orders` (`user_id`, `status`, `total`, `currency`, `updated_at`, `created_at`) values (?, ?, ?, ?, ?, ?)", "orders", 2.2, 6},
	{"insert into `order_items` (`order_id`, `product_id`, `qty`, `price`) values (?, ?, ?, ?), (?, ?, ?, ?)", "order_items", 2.6, 8},
	{"select * from `orders` where `user_id` = ? order by `id` desc limit 15", "orders", 2.8, 1},
	{"select * from `orders` where `orders`.`id` = ? limit 1 for update", "orders", 0.9, 1},
	{"update `orders` set `status` = ?, `paid_at` = ?, `orders`.`updated_at` = ? where `id` = ?", "orders", 1.7, 4},
	{"select * from `payments` where `order_id` = ? and `status` in (?, ?)", "payments", 0.7, 3},
	{"select `sku`, sum(`qty`) as `qty`, sum(`qty` * `price`) as `revenue` from `order_items` inner join `orders` on `orders`.`id` = `order_items`.`order_id` where `orders`.`created_at` between ? and ? group by `sku` order by `revenue` desc", "reports", 380, 2},
	{"select * from `notifications` where `notifiable_id` = ? and `read_at` is null order by `created_at` desc", "notifications", 1.1, 1},
	{"select * from `jobs` where `queue` = ? and ((`reserved_at` is null and `available_at` <= ?)) order by `id` asc limit 1 for update skip locked", "jobs", 0.9, 2},
	{"delete from `sessions` where `last_activity` <= ?", "sessions", 12, 1},
	{"select * from `settings`", "settings", 0.3, 0},
}

var connections = []string{"mysql", "mysql", "mysql", "mysql_read", "pgsql_analytics"}

var eventNames = []string{
	"Illuminate\\Auth\\Events\\Authenticated",
	"Illuminate\\Auth\\Events\\Login",
	"Illuminate\\Database\\Events\\TransactionBeginning",
	"Illuminate\\Database\\Events\\TransactionCommitted",
	"App\\Events\\OrderCreated",
	"App\\Events\\OrderPaid",
	"App\\Events\\CartUpdated",
	"App\\Events\\ProductViewed",
	"App\\Events\\UserRegistered",
	"Illuminate\\Queue\\Events\\JobProcessed",
	"Illuminate\\Cache\\Events\\KeyForgotten",
}

var listeners = []string{
	"App\\Listeners\\SendOrderConfirmation",
	"App\\Listeners\\UpdateStock",
	"App\\Listeners\\RecalculateCart",
	"App\\Listeners\\TrackAnalytics",
	"Closure",
}

var modelClasses = []string{
	"App\\Models\\User", "App\\Models\\Product", "App\\Models\\Order", "App\\Models\\OrderItem",
	"App\\Models\\Cart", "App\\Models\\CartItem", "App\\Models\\Payment", "App\\Models\\Category",
}

var modelActions = []string{"retrieved", "retrieved", "retrieved", "created", "updated", "saved", "deleted"}

var abilities = []string{"view", "update", "delete", "viewAny", "create", "manage-orders", "access-admin"}

var cacheKeys = []string{
	"settings:all", "categories:tree", "product:%d", "product:%d:price", "cart:%d", "user:%d:permissions",
	"rate-limit:api:%d", "search:%x", "reports:sales:daily", "feature-flags",
}

var cacheTypes = []string{"hit", "hit", "hit", "hit", "missed", "set", "forget"}

var logMessages = []struct {
	level   string
	message string
}{
	{"info", "Order created"},
	{"info", "Payment received"},
	{"info", "User logged in"},
	{"debug", "Cart recalculated"},
	{"warning", "Stock is low for product"},
	{"warning", "Slow query detected"},
	{"warning", "Payment provider responded slowly"},
	{"error", "Payment declined by provider"},
	{"error", "Failed to send webhook"},
	{"error", "SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock"},
	{"critical", "Redis connection refused"},
}

var httpHosts = []struct {
	uri      string
	method   string
	medianMs float64
}{
	{"https://api.stripe.com/v1/payment_intents", "POST", 420},
	{"https://api.stripe.com/v1/charges/%x", "GET", 180},
	{"https://api.sendgrid.com/v3/mail/send", "POST", 230},
	{"https://maps.googleapis.com/maps/api/geocode/json", "GET", 140},
	{"https://api.exchangerate.host/latest", "GET", 90},
	{"https://hooks.slack.com/services/T000/B000/%x", "POST", 160},
	{"http://inventory.internal/api/stock/%d", "GET", 35},
}

var jobClasses = []struct {
	class    string
	queue    string
	medianMs float64
}{
	{"App\\Jobs\\SendOrderConfirmationEmail", "emails", 260},
	{"App\\Jobs\\ProcessPayment", "payments", 900},
	{"App\\Jobs\\SyncStockWithWarehouse", "default", 1300},
	{"App\\Jobs\\GenerateInvoicePdf", "default", 2100},
	{"App\\Jobs\\RecalculateProductRatings", "low", 450},
	{"App\\Jobs\\SendPushNotification", "notifications", 120},
	{"App\\Jobs\\ImportProductsChunk", "imports", 3800},
	{"App\\Jobs\\CleanupAbandonedCarts", "low", 700},
	{"App\\Jobs\\IndexProductForSearch", "search", 80},
	{"Illuminate\\Notifications\\SendQueuedNotifications", "notifications", 210},
}

var commands = []struct {
	name     string
	medianMs float64
}{
	{"schedule:run", 350},
	{"orders:expire", 1200},
	{"reports:daily", 45000},
	{"sitemap:generate", 8000},
	{"queue:prune-failed", 150},
	{"cache:prune-stale-tags", 90},
	{"telescope:prune", 600},
}

var scheduledCommands = []struct {
	command     string
	description string
	expression  string
}{
	{"'/usr/local/bin/php' 'artisan' orders:expire", "Expire unpaid orders", "*/5 * * * *"},
	{"'/usr/local/bin/php' 'artisan' reports:daily", "Build the daily sales report", "0 3 * * *"},
	{"'/usr/local/bin/php' 'artisan' sitemap:generate", "Regenerate the sitemap", "0 * * * *"},
	{"Closure", "App\\Jobs\\SyncStockWithWarehouse", "*/10 * * * *"},
	{"Closure", "App\\Jobs\\CleanupAbandonedCarts", "0 */2 * * *"},
}

var taskNames = []string{"cron", "queue-monitor", "metrics-flush", "heartbeat", "ws-broadcast"}

var mailables = []string{"App\\Mail\\OrderConfirmation", "App\\Mail\\PasswordReset", "App\\Mail\\WeeklyDigest"}

var notifications = []string{
	"App\\Notifications\\OrderShipped", "App\\Notifications\\PaymentFailed", "App\\Notifications\\NewLogin",
}

var notificationChannels = []string{"mail", "database", "broadcast", "slack"}

var userAgents = []string{
	"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36",
	"Mozilla/5.0 (iPhone; CPU iPhone OS 19_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.2 Mobile/15E148 Safari/604.1",
	"Mozilla/5.0 (Macintosh; Intel Mac OS X 15_4) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/19.1 Safari/605.1.15",
	"okhttp/5.1.0",
	"ShopApp/4.12.0 (Android 16; Pixel 9)",
}

var skus = []string{"SKU-1001", "SKU-1002", "SKU-2040", "SKU-3300", "SKU-4410", "SKU-5123", "SKU-7788", "SKU-9001"}
