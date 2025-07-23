package com.ahmed_ashraf.clientapplication.Service;

import com.ahmed_ashraf.clientapplication.dto.ClientDeleteRequest;
import com.ahmed_ashraf.clientapplication.Entity.*;
import com.ahmed_ashraf.clientapplication.Repository.*;
import jakarta.transaction.Transactional;
import lombok.RequiredArgsConstructor;
//import org.json.JSONObject;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.util.Date;
import java.util.Optional;


@Service
@RequiredArgsConstructor
public class DeleteService {

    private final DMClientAppDeletedRepository deletedRepo;
    private final LrOfficerRepository officerRepo;

    private final RestTemplate restTemplate = new RestTemplate();

    @Transactional
    public String deleteClient(ClientDeleteRequest request) {
        String url = "http://10.10.246.204/your-delete-api.php";
        HttpHeaders headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_JSON);

        String username = "System";
        String password = "2023@AbaToday";

        String body = String.format("{\"userName\":\"%s\", \"Password\":\"%s\", \"serial\":%d, \"nationalno\":\"%s\"}",
                username, password, request.serial(), request.nationalNo());

        HttpEntity<String> entity = new HttpEntity<>(body, headers);
        ResponseEntity<String> response = restTemplate.postForEntity(url, entity, String.class);

        if (!response.getStatusCode().is2xxSuccessful()) {
            throw new RuntimeException("External delete failed with status: " + response.getStatusCode());
        }
        return "Deleted successfully from external and saved locally";
    }
}

   /*     JSONObject jsonObject=null;
        JSONObject json = new JSONObject(response.getBody());

        String status = json.optString("Status");
        if (!"OK".equalsIgnoreCase(status)) {
            throw new RuntimeException("Deletion not confirmed from external API: " + json);
        }

        String offBranchCode = json.optString("OFF_BRANCH_CODE");
        String offCode = json.optString("OFF_CODE");
        String appDate = json.optString("APPDATE");
        String appLastUpdate = json.optString("LASTUPDATE"); // this becomes APP_LASTUPDATE

        DMClientAppDeleted deleted = new DMClientAppDeleted();
        deleted.setSerial((long) request.serial());
        deleted.setNationalno(request.nationalNo());
        deleted.setOffBranchCode(offBranchCode);
        deleted.setOffCode(offCode);
        deleted.setAppDate(appDate);
        deleted.setAppLastUpdate(appLastUpdate);
        deleted.setLastUpdate(new Date()); // sysdate

        deletedRepo.save(deleted);

        LrOfficerId officerId = new LrOfficerId(offBranchCode, offCode);
        Optional<LrOfficer> officerOpt = officerRepo.findById(officerId);
        LrOfficer officer = officerOpt.orElseGet(() -> {
            LrOfficer newOfficer = new LrOfficer();
            newOfficer.setBranch_code(offBranchCode);
            newOfficer.setCode(offCode);
            newOfficer.setNoOfAppDeleted(0);
            return newOfficer;
        });
        officer.setNoOfAppDeleted(officer.getNoOfAppDeleted() + 1);
        officerRepo.save(officer);

        return "Deleted successfully from external and saved locally";
    }
}*/
