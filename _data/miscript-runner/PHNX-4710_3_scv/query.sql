SELECT p.external_user_id, p.brand_id
FROM profiles p
         JOIN
     (SELECT *, COUNT(*) AS profile_count
      FROM profiles
      WHERE external_user_id IS NOT NULL
        AND customer_id IS NOT NULL
      GROUP BY external_user_id
      HAVING COUNT(*) > 1) s
     ON s.external_user_id = p.external_user_id
WHERE s.customer_id = p.customer_id
GROUP BY p.external_user_id, p.brand_id
ORDER BY p.created_at DESC
