import fs from "node:fs/promises";
import path from "node:path";

const slidesDir = "outputs/manual-videostream-ppt/presentations/videostream-project/slides";

const slide = (n, body) => fs.writeFile(
  path.join(slidesDir, `slide-${String(n).padStart(2, "0")}.mjs`),
  body.trimStart(),
  "utf8",
);

await slide(1, `
import { C, addFooter } from "./common.mjs";
export async function slide01(presentation, ctx) {
  const s = presentation.slides.add();
  ctx.addShape(s, { x: 0, y: 0, w: 1280, h: 720, fill: C.bg });
  ctx.addShape(s, { x: 0, y: 0, w: 1280, h: 720, fill: "#121826" });
  ctx.addShape(s, { x: 54, y: 70, w: 8, h: 510, fill: C.red });
  ctx.addText(s, { x: 90, y: 86, w: 860, h: 56, text: "VideoStream", fontSize: 46, bold: true, color: C.white, typeface: ctx.fonts.title });
  ctx.addText(s, { x: 94, y: 148, w: 760, h: 34, text: "PHP MVC Video Streaming Platform", fontSize: 21, color: C.muted });
  ctx.addText(s, { x: 94, y: 228, w: 720, h: 88, text: "Architecture, workflow, real-time updates, admin/user modules, AJAX, RSS/blog feed, and security concepts.", fontSize: 24, bold: true, color: C.text });
  ctx.addShape(s, { x: 820, y: 108, w: 300, h: 188, fill: "#0F172A", line: { fill: "#334155", width: 1, style: "solid" } });
  ctx.addText(s, { x: 850, y: 135, w: 240, h: 32, text: "Project Viva Deck", fontSize: 22, bold: true, color: C.white, align: "center" });
  ctx.addText(s, { x: 858, y: 182, w: 224, h: 72, text: "Student: __________\\nGuide: __________\\nDepartment: __________", fontSize: 14, color: C.muted, align: "center" });
  ctx.addShape(s, { x: 94, y: 398, w: 180, h: 58, fill: C.red });
  ctx.addText(s, { x: 112, y: 416, w: 144, h: 22, text: "MVC + Real-time", fontSize: 15, bold: true, color: C.white, align: "center" });
  ctx.addText(s, { x: 94, y: 612, w: 760, h: 24, text: "Built from actual files: public/index.php, app/controllers, app/models, user.js, user.css, websocket/server.php", fontSize: 12, color: C.muted });
  addFooter(s, ctx, 1);
  return s;
}
`);

await slide(2, `
import { slideBase, addFooter, card, notes, C } from "./common.mjs";
export async function slide02(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Project overview: two panels, one streaming workflow");
  card(s, ctx, 70, 145, 340, 160, "User panel", "Browse videos, trending list, categories, watchlist, history, profile, subscription request, reviews, reports, notifications.", C.red, "U");
  card(s, ctx, 470, 145, 340, 160, "Admin panel", "Manage dashboard, videos, categories, users, reviews, reports, payments, subscriptions, messages, notifications, settings, activity logs.", C.blue, "A");
  card(s, ctx, 870, 145, 340, 160, "Shared platform", "Routing, MVC controllers/models, sessions, AJAX endpoints, WebSocket signaling, reusable CSS/JS assets.", C.green, "S");
  ctx.addText(s, { x: 80, y: 365, w: 1050, h: 42, text: "Core idea: users consume content; admins control content, users, payments, moderation, and platform settings.", fontSize: 22, bold: true, color: C.white });
  card(s, ctx, 80, 445, 250, 100, "Content", "Videos, categories, thumbnails, access levels", C.red);
  card(s, ctx, 360, 445, 250, 100, "Engagement", "Wishlist, history, reviews, reports", C.amber);
  card(s, ctx, 640, 445, 250, 100, "Operations", "Admin users, payments, messages", C.blue);
  card(s, ctx, 920, 445, 250, 100, "Real-time", "WebSocket signal + AJAX refresh", C.green);
  notes(s, ctx, "Start with what the system does before going technical.");
  addFooter(s, ctx, 2);
  return s;
}
`);

await slide(3, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide03(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Layered MVC architecture");
  const layers = [
    ["Browser / UI", "User/admin pages, forms, video modal"],
    ["public/index.php", "Front controller, pretty routes, 404"],
    ["Controllers", "AuthController, HomeController, VideoController, split action controllers"],
    ["Models", "UserModel, VideoModel, ReviewModel, ReportModel, NotificationModel"],
    ["Database", "users, videos, reviews, reports, notifications, subscriptions"]
  ];
  layers.forEach((l, i) => step(s, ctx, i + 1, 170, 135 + i * 88, 760, 64, l[0], l[1], [C.red, C.blue, C.amber, C.green, C.purple][i]));
  for (let i = 0; i < 4; i++) arrow(s, ctx, 550, 200 + i * 88, 550, 222 + i * 88, C.red);
  ctx.addShape(s, { x: 970, y: 150, w: 190, h: 310, fill: "#0F172A", line: { fill: "#334155", width: 1, style: "solid" } });
  ctx.addText(s, { x: 995, y: 176, w: 140, h: 32, text: "Views + Assets", fontSize: 18, bold: true, color: C.white, align: "center" });
  ctx.addText(s, { x: 995, y: 225, w: 140, h: 120, text: "home.php\\nuser/pages/*\\nadmin/*.php\\nuser.css\\nuser.js", fontSize: 14, color: C.muted, align: "center" });
  ctx.addText(s, { x: 995, y: 380, w: 140, h: 36, text: "Render HTML\\nand behavior", fontSize: 13, color: C.green, align: "center" });
  notes(s, ctx, "Explain MVC as separation: routing, request logic, DB logic, UI.");
  addFooter(s, ctx, 3);
  return s;
}
`);

await slide(4, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide04(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Request lifecycle flowchart");
  const items = [
    ["Browser request", "/profile, /admin/videos, ?action=save_review"],
    ["public/index.php", "Normalize URL, map module/page/action"],
    ["Controller", "Validate login/role and call model"],
    ["Model + DB", "Prepared query reads or writes data"],
    ["Response", "View HTML or JSON for AJAX"],
    ["Browser update", "Full page render or user.js DOM update"]
  ];
  items.forEach((it, i) => {
    const row = i < 3 ? 0 : 1, col = i % 3;
    step(s, ctx, i + 1, 75 + col * 390, 145 + row * 190, 320, 96, it[0], it[1], [C.red, C.blue, C.amber, C.green, C.purple, C.red][i]);
  });
  arrow(s, ctx, 395, 193, 465, 193, C.red); arrow(s, ctx, 785, 193, 855, 193, C.red);
  arrow(s, ctx, 1015, 245, 1015, 332, C.red); arrow(s, ctx, 855, 383, 785, 383, C.red); arrow(s, ctx, 465, 383, 395, 383, C.red);
  notes(s, ctx, "This slide proves every request has a controlled path.");
  addFooter(s, ctx, 4);
  return s;
}
`);

await slide(5, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide05(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Routing map and 404 protection");
  step(s, ctx, 1, 90, 145, 300, 90, "Pretty route", "/login -> auth/user_login\\n/admin/videos -> admin/videos", C.blue);
  step(s, ctx, 2, 460, 145, 300, 90, "User pretty page", "/profile -> user/home\\nwith upage=profile", C.green);
  step(s, ctx, 3, 830, 145, 300, 90, "Action route", "?action=save_review -> UserFeedbackController", C.amber);
  arrow(s, ctx, 390, 190, 460, 190); arrow(s, ctx, 760, 190, 830, 190);
  ctx.addShape(s, { x: 125, y: 315, w: 1020, h: 145, fill: "#0F172A", line: { fill: "#334155", width: 1, style: "solid" } });
  ctx.addText(s, { x: 160, y: 345, w: 900, h: 36, text: "Invalid module/page/action now renders app/views/errors/404.php", fontSize: 24, bold: true, color: C.white, align: "center" });
  ctx.addText(s, { x: 220, y: 402, w: 780, h: 30, text: "This prevents random URLs from silently loading the home page.", fontSize: 15, color: C.muted, align: "center" });
  notes(s, ctx, "Mention why routing cleanup matters for professionalism and security.");
  addFooter(s, ctx, 5);
  return s;
}
`);

await slide(6, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide06(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Authentication workflows: user and admin");
  step(s, ctx, 1, 70, 145, 270, 86, "User sign-in", "AuthController::userAuthenticate", C.red);
  step(s, ctx, 2, 390, 145, 270, 86, "Check password", "AuthModel + password_verify", C.amber);
  step(s, ctx, 3, 710, 145, 270, 86, "Check status", "blocked if suspended/deleted", C.blue);
  step(s, ctx, 4, 1010, 145, 190, 86, "Session", "$_SESSION user_id", C.green);
  arrow(s, ctx, 340, 188, 390, 188); arrow(s, ctx, 660, 188, 710, 188); arrow(s, ctx, 980, 188, 1010, 188);
  step(s, ctx, 1, 70, 350, 270, 86, "Admin login", "AuthController::adminAuthenticate", C.blue);
  step(s, ctx, 2, 390, 350, 270, 86, "Admin lookup", "findAdminByEmail", C.purple);
  step(s, ctx, 3, 710, 350, 270, 86, "Role session", "$_SESSION role=admin", C.amber);
  step(s, ctx, 4, 1010, 350, 190, 86, "Admin panel", "DashboardController", C.green);
  arrow(s, ctx, 340, 393, 390, 393); arrow(s, ctx, 660, 393, 710, 393); arrow(s, ctx, 980, 393, 1010, 393);
  notes(s, ctx, "Explain user and admin are separate login paths but share session idea.");
  addFooter(s, ctx, 6);
  return s;
}
`);

await slide(7, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide07(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Email verification and no-reply mail workflow");
  const items = [
    ["Register form", "name, email, password"],
    ["Validate", "format, duplicate, strength"],
    ["Generate OTP", "random_int 6 digits"],
    ["Session pending", "pending_registration"],
    ["PHPMailer SMTP", "APP_NAME No Reply"],
    ["Verify OTP", "create account + welcome mail"]
  ];
  items.forEach((it, i) => {
    const x = 70 + (i % 3) * 390, y = 145 + Math.floor(i / 3) * 180;
    step(s, ctx, i + 1, x, y, 315, 88, it[0], it[1], [C.red, C.amber, C.green, C.blue, C.purple, C.red][i]);
  });
  arrow(s, ctx, 385, 188, 460, 188); arrow(s, ctx, 775, 188, 850, 188); arrow(s, ctx, 1005, 235, 1005, 322);
  arrow(s, ctx, 850, 368, 775, 368); arrow(s, ctx, 460, 368, 385, 368);
  ctx.addText(s, { x: 90, y: 545, w: 1050, h: 30, text: "Files: AuthController.php -> sendOtpMail(), verifyRegistrationOtp(), sendWelcomeMail(); config/app.php -> SMTP_* constants.", fontSize: 14, color: C.muted });
  notes(s, ctx, "Say sender uses SMTP email, display name is No Reply, reply-to is no-reply@host.");
  addFooter(s, ctx, 7);
  return s;
}
`);

await slide(8, `
import { slideBase, addFooter, card, arrow, notes, C } from "./common.mjs";
export async function slide08(presentation, ctx) {
  const s = slideBase(presentation, ctx, "User panel: page structure");
  card(s, ctx, 70, 150, 260, 115, "home.php", "Main user shell: sidebar, topbar, content router, footer, video modal config.", C.red);
  card(s, ctx, 390, 150, 260, 115, "includes/*", "sidebar.php, topbar.php, video_modal.php, recent_blogs.php", C.blue);
  card(s, ctx, 710, 150, 360, 115, "pages/*", "browse, trending, categories, watchlist, history, profile, subscription", C.green);
  arrow(s, ctx, 330, 205, 390, 205); arrow(s, ctx, 650, 205, 710, 205);
  ctx.addText(s, { x: 80, y: 340, w: 1100, h: 34, text: "HomeController prepares the data; home.php chooses the correct page partial through activePage/upage.", fontSize: 22, bold: true, color: C.white });
  card(s, ctx, 85, 425, 245, 90, "Data", "featured, trending, categories, wishlist, history", C.amber);
  card(s, ctx, 365, 425, 245, 90, "Behavior", "public/assets/js/user.js", C.purple);
  card(s, ctx, 645, 425, 245, 90, "Style", "public/assets/css/user.css", C.blue);
  card(s, ctx, 925, 425, 245, 90, "Modal", "shared video player + review/report", C.red);
  notes(s, ctx, "Explain why we split view files: easier navigation and safer future work.");
  addFooter(s, ctx, 8);
  return s;
}
`);

await slide(9, `
import { slideBase, addFooter, card, notes, C } from "./common.mjs";
export async function slide09(presentation, ctx) {
  const s = slideBase(presentation, ctx, "User panel modules and functionality");
  const modules = [
    ["Home", "featured, trending, stats, activity"],
    ["Browse", "all published videos"],
    ["Trending", "views-based ordering"],
    ["Categories", "genre/category filter"],
    ["Watchlist", "saved videos"],
    ["History", "watched/progress data"],
    ["Profile", "name, password, delete account"],
    ["Subscription", "plan request to admin"],
    ["Notifications", "feed updates and clear"]
  ];
  modules.forEach((m, i) => card(s, ctx, 70 + (i % 3) * 390, 135 + Math.floor(i / 3) * 145, 325, 92, m[0], m[1], [C.red, C.blue, C.green, C.amber, C.purple, C.red, C.blue, C.green, C.amber][i]));
  notes(s, ctx, "Walk module-by-module; keep it simple and functional.");
  addFooter(s, ctx, 9);
  return s;
}
`);

await slide(10, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide10(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Video playback workflow");
  const items = [
    ["Click card", "js-open-video"],
    ["Read payload", "u_video_payload JSON"],
    ["Plan check", "free/basic/premium rank"],
    ["Open modal", "video_modal.php"],
    ["Playback", "play, pause, seek, volume, fullscreen"],
    ["Server updates", "record_view + save_progress"],
    ["Feedback", "review/rating + report from modal"]
  ];
  items.forEach((it, i) => step(s, ctx, i + 1, 70 + (i % 4) * 300, 145 + Math.floor(i / 4) * 190, 240, 90, it[0], it[1], [C.red, C.blue, C.amber, C.green, C.purple, C.red, C.blue][i]));
  arrow(s, ctx, 310, 188, 370, 188); arrow(s, ctx, 610, 188, 670, 188); arrow(s, ctx, 910, 188, 970, 188);
  arrow(s, ctx, 1090, 235, 1090, 322); arrow(s, ctx, 970, 378, 910, 378); arrow(s, ctx, 670, 378, 610, 378);
  notes(s, ctx, "Mention video_player code is in user.js and server writes are in PlaybackController.");
  addFooter(s, ctx, 10);
  return s;
}
`);

await slide(11, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide11(presentation, ctx) {
  const s = slideBase(presentation, ctx, "AJAX workflow: action without full page reload");
  step(s, ctx, 1, 70, 190, 230, 95, "User action", "click wishlist / submit review", C.red);
  step(s, ctx, 2, 340, 190, 230, 95, "user.js fetch()", "POST action endpoint", C.blue);
  step(s, ctx, 3, 610, 190, 230, 95, "Controller", "validate + call model", C.amber);
  step(s, ctx, 4, 880, 190, 230, 95, "Model/DB", "write/read data", C.green);
  arrow(s, ctx, 300, 238, 340, 238); arrow(s, ctx, 570, 238, 610, 238); arrow(s, ctx, 840, 238, 880, 238);
  step(s, ctx, 5, 340, 390, 230, 95, "JSON response", "{ ok, message, count }", C.purple);
  step(s, ctx, 6, 610, 390, 230, 95, "UI update", "toast, counter, DOM", C.red);
  arrow(s, ctx, 990, 285, 455, 390, C.red); arrow(s, ctx, 570, 438, 610, 438);
  ctx.addText(s, { x: 85, y: 555, w: 1050, h: 28, text: "Used by: wishlist_toggle, save_review, save_report, update_profile, subscription_request, save_progress, record_view, feed_json.", fontSize: 14, color: C.muted });
  notes(s, ctx, "AJAX improves UX because user does not wait for full page reload.");
  addFooter(s, ctx, 11);
  return s;
}
`);

await slide(12, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide12(presentation, ctx) {
  const s = slideBase(presentation, ctx, "WebSocket real-time workflow");
  const actors = [
    ["Controller", "data changes"],
    ["WsPublisher.php", "push topic to bridge"],
    ["Bridge :8081", "TCP event channel"],
    ["websocket/server.php", "Ratchet broadcasts"],
    ["Browser WS :8080", "receives signal"],
    ["feed_json AJAX", "fetch real data"],
    ["DOM rebuild", "notifications/feed update"]
  ];
  actors.forEach((a, i) => step(s, ctx, i + 1, 56 + i * 172, 195, 145, 105, a[0], a[1], [C.red, C.amber, C.blue, C.green, C.purple, C.red, C.green][i]));
  for (let i = 0; i < 6; i++) arrow(s, ctx, 200 + i * 172, 246, 228 + i * 172, 246, C.red);
  ctx.addText(s, { x: 98, y: 390, w: 1000, h: 36, text: "Important design: WebSocket sends a signal only; the browser uses AJAX/fetch to get fresh trusted data.", fontSize: 22, bold: true, color: C.white, align: "center" });
  ctx.addText(s, { x: 145, y: 470, w: 900, h: 28, text: "Files: app/core/WsPublisher.php, websocket/server.php, public/assets/js/user.js, HomeController::feed_json()", fontSize: 14, color: C.muted, align: "center" });
  notes(s, ctx, "This is the strongest real-time architecture slide.");
  addFooter(s, ctx, 12);
  return s;
}
`);

await slide(13, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide13(presentation, ctx) {
  const s = slideBase(presentation, ctx, "RSS / Blog feed workflow");
  step(s, ctx, 1, 120, 185, 250, 90, "Home page", "Recent Blogs section", C.red);
  step(s, ctx, 2, 430, 185, 250, 90, "Fetch feed/API", "blog/wp-json/wp/v2/posts", C.blue);
  step(s, ctx, 3, 740, 185, 250, 90, "Decode data", "json_decode posts", C.amber);
  arrow(s, ctx, 370, 230, 430, 230); arrow(s, ctx, 680, 230, 740, 230);
  step(s, ctx, 4, 275, 380, 270, 95, "Success", "Render blog cards", C.green);
  step(s, ctx, 5, 695, 380, 270, 95, "Fallback", "includes/recent_blogs.php", C.purple);
  arrow(s, ctx, 865, 275, 420, 380, C.green); arrow(s, ctx, 865, 275, 830, 380, C.purple);
  notes(s, ctx, "Explain external content does not break page because fallback exists.");
  addFooter(s, ctx, 13);
  return s;
}
`);

await slide(14, `
import { slideBase, addFooter, card, notes, C } from "./common.mjs";
export async function slide14(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Admin panel modules and responsibilities");
  const modules = [
    ["Dashboard", "charts, counts, recent activity"],
    ["Videos", "add, edit, delete, upload"],
    ["Categories", "manage video taxonomy"],
    ["Users", "add, suspend, activate, delete, export"],
    ["Reviews", "moderate ratings/comments"],
    ["Reports", "resolve or dismiss video reports"],
    ["Payments", "payment records"],
    ["Subscriptions", "plans and subscriptions"],
    ["Messages", "plan requests and contact messages"],
    ["Settings", "SMTP, platform, toggles"],
    ["Notifications", "admin alerts"],
    ["Activity logs", "audit admin actions"]
  ];
  modules.forEach((m, i) => card(s, ctx, 55 + (i % 4) * 300, 132 + Math.floor(i / 4) * 150, 255, 92, m[0], m[1], [C.red, C.blue, C.green, C.amber, C.purple, C.red, C.blue, C.green, C.amber, C.purple, C.red, C.blue][i]));
  notes(s, ctx, "Admin panel is the operational control room of the project.");
  addFooter(s, ctx, 14);
  return s;
}
`);

await slide(15, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide15(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Admin video and category management flow");
  step(s, ctx, 1, 80, 150, 230, 90, "Admin opens Videos", "VideoController::index", C.red);
  step(s, ctx, 2, 360, 150, 230, 90, "Save action", "title, file, thumbnail, category", C.blue);
  step(s, ctx, 3, 640, 150, 230, 90, "VideoModel", "create/update/delete", C.green);
  step(s, ctx, 4, 920, 150, 230, 90, "Notify", "ActivityLog + WsPublisher", C.amber);
  arrow(s, ctx, 310, 195, 360, 195); arrow(s, ctx, 590, 195, 640, 195); arrow(s, ctx, 870, 195, 920, 195);
  step(s, ctx, 1, 220, 380, 250, 90, "Categories", "CategoryController", C.purple);
  step(s, ctx, 2, 530, 380, 250, 90, "CategoryModel", "create/edit/delete", C.blue);
  step(s, ctx, 3, 840, 380, 250, 90, "Video filtering", "Category pages use category data", C.green);
  arrow(s, ctx, 470, 425, 530, 425); arrow(s, ctx, 780, 425, 840, 425);
  notes(s, ctx, "Show content management path and how categories support discovery.");
  addFooter(s, ctx, 15);
  return s;
}
`);

await slide(16, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide16(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Admin user management flow");
  step(s, ctx, 1, 70, 150, 250, 90, "User list", "UserController::index", C.blue);
  step(s, ctx, 2, 370, 150, 250, 90, "Create user", "createManual + welcome mail", C.green);
  step(s, ctx, 3, 670, 150, 250, 90, "Suspend/activate", "updateStatus", C.amber);
  step(s, ctx, 4, 970, 150, 190, 90, "Delete/export", "delete or CSV", C.red);
  arrow(s, ctx, 320, 195, 370, 195); arrow(s, ctx, 620, 195, 670, 195); arrow(s, ctx, 920, 195, 970, 195);
  ctx.addShape(s, { x: 160, y: 350, w: 900, h: 110, fill: "#0F172A", line: { fill: "#334155", width: 1, style: "solid" } });
  ctx.addText(s, { x: 195, y: 372, w: 830, h: 30, text: "Suspend/delete protection", fontSize: 24, bold: true, color: C.white, align: "center" });
  ctx.addText(s, { x: 210, y: 420, w: 800, h: 25, text: "UserController purges active sessions and AuthController blocks future login if status is not active.", fontSize: 15, color: C.muted, align: "center" });
  notes(s, ctx, "This answers the common viva question: what happens to suspended users?");
  addFooter(s, ctx, 16);
  return s;
}
`);

await slide(17, `
import { slideBase, addFooter, step, arrow, notes, C } from "./common.mjs";
export async function slide17(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Review, rating and report workflow");
  step(s, ctx, 1, 80, 160, 260, 90, "User modal", "rating stars, comment, report form", C.red);
  step(s, ctx, 2, 400, 160, 260, 90, "AJAX submit", "save_review / save_report", C.blue);
  step(s, ctx, 3, 720, 160, 260, 90, "UserFeedbackController", "validate + call model", C.amber);
  arrow(s, ctx, 340, 205, 400, 205); arrow(s, ctx, 660, 205, 720, 205);
  step(s, ctx, 4, 220, 380, 260, 90, "ReviewModel", "save rating/comment", C.green);
  step(s, ctx, 5, 520, 380, 260, 90, "ReportModel", "create video report", C.purple);
  step(s, ctx, 6, 820, 380, 260, 90, "Admin moderation", "reviews.php / reports.php", C.red);
  arrow(s, ctx, 850, 250, 350, 380, C.green); arrow(s, ctx, 850, 250, 650, 380, C.purple); arrow(s, ctx, 780, 425, 820, 425);
  notes(s, ctx, "Explain user feedback becomes admin work for moderation.");
  addFooter(s, ctx, 17);
  return s;
}
`);

await slide(18, `
import { slideBase, addFooter, card, arrow, notes, C } from "./common.mjs";
export async function slide18(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Database and model workflow");
  card(s, ctx, 70, 150, 260, 115, "Controllers", "Small request handlers call model methods.", C.red);
  card(s, ctx, 400, 150, 260, 115, "Models", "UserModel, VideoModel, ReviewModel, ReportModel, NotificationModel, SubscriptionModel, MessageModel.", C.blue);
  card(s, ctx, 730, 150, 260, 115, "Database", "Persistent project data: users, videos, plans, reviews, reports, notifications.", C.green);
  arrow(s, ctx, 330, 207, 400, 207); arrow(s, ctx, 660, 207, 730, 207);
  ctx.addText(s, { x: 92, y: 350, w: 1000, h: 36, text: "Refactoring goal: controllers decide workflow; models own SQL and prepared statements.", fontSize: 24, bold: true, color: C.white, align: "center" });
  card(s, ctx, 100, 450, 300, 85, "Read examples", "getPublished, getTrending, getWishlistItems", C.amber);
  card(s, ctx, 470, 450, 300, 85, "Write examples", "toggleWishlist, saveWatchProgress, saveUserReview", C.purple);
  card(s, ctx, 840, 450, 300, 85, "Safety", "prepared statements + user_id scoping", C.green);
  notes(s, ctx, "Make clear that DB queries should not live inside the view.");
  addFooter(s, ctx, 18);
  return s;
}
`);

await slide(19, `
import { slideBase, addFooter, card, notes, C } from "./common.mjs";
export async function slide19(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Security concepts used in the project");
  const items = [
    ["Sessions", "PHPSESSID cookie + server-side $_SESSION"],
    ["Password hashing", "password_hash and password_verify"],
    ["Role guards", "requireAdmin and requireActiveUser"],
    ["Status checks", "suspended/deleted users cannot login"],
    ["Prepared statements", "model methods bind values"],
    ["Output escaping", "h() helper for HTML output"],
    ["Input validation", "IDs, email, password, rating, report reason"],
    ["404 protection", "unknown routes render errors/404.php"]
  ];
  items.forEach((it, i) => card(s, ctx, 70 + (i % 4) * 290, 145 + Math.floor(i / 4) * 170, 245, 105, it[0], it[1], [C.red, C.blue, C.green, C.amber, C.purple, C.red, C.blue, C.green][i]));
  notes(s, ctx, "Security is not one feature; it is applied across routing, auth, DB, and views.");
  addFooter(s, ctx, 19);
  return s;
}
`);

await slide(20, `
import { slideBase, addFooter, card, arrow, notes, C } from "./common.mjs";
export async function slide20(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Refactoring improvements: cleaner MVC");
  card(s, ctx, 70, 145, 270, 110, "Before", "home.php had mixed view, CSS, JS, modal logic, and large controller behavior.", C.red);
  card(s, ctx, 470, 145, 270, 110, "Small moves", "Extract CSS, JS, view partials, controller actions, model queries.", C.amber);
  card(s, ctx, 870, 145, 270, 110, "After", "Cleaner home.php, smaller controllers, user.css, user.js, organized routing.", C.green);
  arrow(s, ctx, 340, 200, 470, 200); arrow(s, ctx, 740, 200, 870, 200);
  ctx.addText(s, { x: 95, y: 335, w: 990, h: 34, text: "Actions moved to smaller controllers", fontSize: 24, bold: true, color: C.white, align: "center" });
  const controllers = ["WishlistController", "ProfileController", "PlaybackController", "UserFeedbackController", "UserSubscriptionController", "HistoryController", "UserNotificationController"];
  controllers.forEach((c, i) => ctx.addText(s, { x: 140 + (i % 2) * 480, y: 400 + Math.floor(i / 2) * 42, w: 410, h: 26, text: c, fontSize: 15, bold: true, color: [C.red, C.blue, C.green, C.amber][i % 4] }));
  notes(s, ctx, "Mention the rule followed during refactor: one small move, test.");
  addFooter(s, ctx, 20);
  return s;
}
`);

await slide(21, `
import { slideBase, addFooter, card, notes, C } from "./common.mjs";
export async function slide21(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Actual file map for viva answers");
  card(s, ctx, 70, 135, 340, 112, "Routing", "public/index.php\\napp/views/errors/404.php", C.red);
  card(s, ctx, 470, 135, 340, 112, "User controllers", "HomeController, WishlistController, ProfileController, PlaybackController, UserFeedbackController", C.blue);
  card(s, ctx, 870, 135, 300, 112, "Admin controllers", "AdminController, UserController, VideoController, ReviewController, ReportController", C.green);
  card(s, ctx, 70, 320, 340, 112, "Models", "UserModel, VideoModel, ReviewModel, ReportModel, NotificationModel, MessageModel", C.amber);
  card(s, ctx, 470, 320, 340, 112, "User views/assets", "app/views/user/home.php\\npublic/assets/js/user.js\\npublic/assets/css/user.css", C.purple);
  card(s, ctx, 870, 320, 300, 112, "Real-time", "websocket/server.php\\napp/core/WsPublisher.php", C.red);
  notes(s, ctx, "Use this slide when examiner asks: where is this implemented?");
  addFooter(s, ctx, 21);
  return s;
}
`);

await slide(22, `
import { slideBase, addFooter, card, notes, C } from "./common.mjs";
export async function slide22(presentation, ctx) {
  const s = slideBase(presentation, ctx, "Final summary: what the project demonstrates");
  card(s, ctx, 95, 150, 300, 120, "Architecture", "Layered MVC with a front controller, route map, controller split, and model-owned queries.", C.red);
  card(s, ctx, 490, 150, 300, 120, "User experience", "Video browsing, playback, wishlist, history, review/report, profile, subscriptions.", C.blue);
  card(s, ctx, 885, 150, 300, 120, "Admin control", "Dashboard, videos, categories, users, payments, subscriptions, reviews, reports, settings.", C.green);
  card(s, ctx, 285, 380, 300, 120, "Interactivity", "AJAX actions, toasts, DOM refresh, progress saving, modal controls.", C.amber);
  card(s, ctx, 685, 380, 300, 120, "Real-time + security", "WebSocket signaling, feed_json refresh, sessions, role checks, prepared statements.", C.purple);
  ctx.addText(s, { x: 210, y: 580, w: 850, h: 34, text: "VideoStream is a practical PHP MVC platform with real user/admin workflows and maintainable structure.", fontSize: 21, bold: true, color: C.white, align: "center" });
  notes(s, ctx, "Close by connecting the technical architecture to project usefulness.");
  addFooter(s, ctx, 22);
  return s;
}
`);

console.log("Generated 22 slide modules in " + slidesDir);
