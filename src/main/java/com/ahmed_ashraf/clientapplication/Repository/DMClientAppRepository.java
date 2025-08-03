package com.ahmed_ashraf.clientapplication.Repository;

import com.ahmed_ashraf.clientapplication.Entity.DMClientApp;
import com.ahmed_ashraf.clientapplication.Entity.DMClientAppId;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface DMClientAppRepository extends JpaRepository<DMClientApp, DMClientAppId> {

    @Query(value = """
        SELECT dm.serial, dm.NATIONALNO,
               CASE
                    WHEN NVL(emp.EMPSTAT_CODE, '000') = '003' THEN 'A'
                    ELSE 'T'
               END AS EMP_STATUS
        FROM
            dm_clientapp dm,
            (SELECT lm_allemp.govern_code, lm_allemp.branch_code, lm_allemp.CODE, lm_allemp.NAMEA EMP_NAME, s.namea EMP_JOB, dep.NAMEA EMP_DEP, lm_allemp.EMPSTAT_CODE EMPSTAT_CODE, lm_allemp.OFFICER_BRANCH_CODE, lm_allemp.OFFICER_CODE
             FROM lm_allemp, lr_subsction s, PR_DEPARTMENT dep
             WHERE lm_allemp.SUBSCTION_CODE = s.CODE
             AND dep.code = lm_allemp.DEP_CODE) emp,
            lr_officer lr,
            lr_branch brn,
            lr_govern gov,
            lm_client lm,
            lm_applic app
        WHERE
            dm.off_BRANCH_CODE = emp.OFFICER_BRANCH_CODE (+)
            AND dm.off_CODE = emp.OFFICER_CODE (+)
            AND dm.off_BRANCH_CODE = lr.branch_code (+)
            AND dm.off_CODE = lr.code (+)
            AND dm.OFF_BRANCH_CODE = brn.code (+)
            AND brn.govern_code = gov.code (+)
            AND dm.NATIONALNO = lm.NATIONALNO (+)
            AND dm.SERIAL = app.CAPP_SERIAL (+)
            AND lm.branch_code = app.client_branch_code (+)
            AND lm.code = app.client_code (+)
            AND dm.APP_STAT in ('C')
            AND TO_DATE(dm.APPDATE, 'dd-mm-yyyy') >= TO_DATE('2025-01-20', 'yyyy-mm-dd')
            AND dm.emp_code IS NULL
            AND dm.emp_branch_code IS NULL
            AND dm.app_flag = 'C'
            AND CASE
                WHEN dm.off_CODE IS NOT NULL
                     AND dm.APP_STAT = 'C'
                THEN 1
                END =1
        """, nativeQuery = true)
    List<Object[]> findSerialsAndNIDs();
}

