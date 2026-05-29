<script>
const USER_PLAN_LEVEL = (function(p) {
  p = (p || 'free').toLowerCase();
  if (p.includes('premium')) return 'premium';
  if (p.includes('basic'))   return 'basic';
  return 'free';
})(<?= json_encode($userPlanLevel ?? null) ?>);
const PLAN_RANK = { free: 0, basic: 1, premium: 2 };
const SUBSCRIPTION_URL = '<?= u_page_url('subscription') ?>';
window.USER_PLAN_LEVEL = USER_PLAN_LEVEL;
window.PLAN_RANK = PLAN_RANK;
window.SUBSCRIPTION_URL = SUBSCRIPTION_URL;

</script>
