package com.ahmed_ashraf.clientapplication.Service;

import com.ahmed_ashraf.clientapplication.Entity.*;
import com.ahmed_ashraf.clientapplication.Repository.DMClientAppRepository;
import com.ahmed_ashraf.clientapplication.Repository.LrOfficerRepository;
import com.ahmed_ashraf.clientapplication.dto.DeleteRequestDTO;
import com.ahmed_ashraf.clientapplication.dto.DeleteResponse;
import com.ahmed_ashraf.clientapplication.dto.DeletedRecordDTO;
import com.ahmed_ashraf.clientapplication.dto.SerialNationalDTO;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import jakarta.transaction.Transactional;
import lombok.RequiredArgsConstructor;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.util.List;

@Service
@RequiredArgsConstructor
public class DeleteClientAppService {

 //   private final String API_URL = "http://localhost/opr/delete__records.php";
    private final String API_URL = "https://opr.aba-apps.com:1504/webserv/delete__records.php";
    private final String AUTH_TOKEN = "jvPG6MdrLiVjOFY7aAXzeFct85ADAP";
 //   private final DMClientAppDeletedRepository deletedRepo;

    @Autowired
    private LrOfficerRepository lrOfficerRepository;

    @Autowired
    private DMClientAppRepository dmClientAppRepository;

    @Transactional  //  Ensures atomicity of save + update
    public void saveDeletedRecords(List<DeletedRecordDTO> records) {
        for (DeletedRecordDTO dto : records) {
            try {
            /*    // commented for now by madam salwa
                DMClientAppDeleted entity = new DMClientAppDeleted();
                SimpleDateFormat formatter = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
                String formattedDate = formatter.format(new Date());

                DMClientAppDeletedId id = new DMClientAppDeletedId(dto.getSerial(), dto.getNationalno());
                entity.setId(id);
                entity.setEmp_status(dto.getEmp_status());
                entity.setOffBranchCode(dto.getOffBranchCode());
                entity.setOffCode(dto.getOffCode());
                entity.setAppDate(dto.getAppDate());
                entity.setAppLastUpdate(dto.getLastUpdate());      //  From PHP
                entity.setLastUpdate(formattedDate);               //  Current system date

                deletedRepo.save(entity);


                // === Now update LR_OFFICER ===
                LrOfficerId officerId = new LrOfficerId(dto.getOffBranchCode(), dto.getOffCode());

                lrOfficerRepository.findById(officerId).ifPresentOrElse(officer -> {
                    // officer exists → increment
                    Integer current = officer.getNoOfAppDeleted();
                    if (current == null) current = 0;
                    officer.setNoOfAppDeleted(current + 1);
                    lrOfficerRepository.save(officer);
                }, () -> {
                    throw new RuntimeException("Officer not found for update: " + officerId);
                });
                */

                // === Update DM_CLIENTAPP ===
                DMClientAppId clientAppId = new DMClientAppId(dto.getSerial(), dto.getNationalno());
                DMClientApp clientApp = dmClientAppRepository.findById(clientAppId)
                        .orElseThrow(() -> new RuntimeException("Client App not found for update: " + clientAppId));

                clientApp.setOff_CODE(null);
                clientApp.setAPP_STAT("N");
                dmClientAppRepository.save(clientApp);

            } catch (Exception ex) {
                // You can log specific failure here
                throw new RuntimeException("Failed to process record for Serial: " + dto.getSerial() + " And National: " + dto.getNationalno(), ex);
            }
        }
    }
    public String deleteClientApps(List<SerialNationalDTO> records) {
        try {
            // Prepare the request payload
            DeleteRequestDTO requestDTO = new DeleteRequestDTO();
            requestDTO.setUsername("System");
            requestDTO.setPassword("2023@AbaToday");
            requestDTO.setRecords(records);

            // Set headers
            HttpHeaders headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.set("Authorization", "Bearer " + AUTH_TOKEN);

            // Convert request to JSON
            ObjectMapper mapper = new ObjectMapper();
            HttpEntity<String> entity = new HttpEntity<>(mapper.writeValueAsString(requestDTO), headers);

            // Log the outgoing request
        /*    System.out.println("===================================");
            System.out.println("JSON Request: " + mapper.writeValueAsString(requestDTO));
            System.out.println("===================================");
        */
            // Send request
            RestTemplate restTemplate = new RestTemplate();
            ResponseEntity<String> response = restTemplate.postForEntity(API_URL, entity, String.class);

            String rawResponse = response.getBody();

            if (rawResponse == null || rawResponse.isEmpty()) {
                System.out.println("Response body is null or empty.");
                return "FAILED";
            }

        //    System.out.println("Raw PHP Response: " + rawResponse);

            // Parse JSON response correctly
            JsonNode rootNode = mapper.readTree(rawResponse);
            DeleteResponse deleteResponse = mapper.treeToValue(rootNode, DeleteResponse.class);

        //    System.out.println("Message: " + deleteResponse.getMessage());

            if ("OK".equalsIgnoreCase(deleteResponse.getStatus())) {
                if (deleteResponse.getDeleted_records() != null && !deleteResponse.getDeleted_records().isEmpty()) {
                    saveDeletedRecords(deleteResponse.getDeleted_records());

                    int count = deleteResponse.getDeleted_records().size();
                    System.out.println("Number of deleted records: " + count);

                    int updatedOfficers = dmClientAppRepository.updateRemainedOfficers();
                    if (updatedOfficers > 0) {
                        System.out.println("Update successful for the additional " + updatedOfficers + " officers");
                    } else {
                        System.out.println("No matching officers found");
                    }

                /*
                    for (DeletedRecordDTO record : deleteResponse.getDeleted_records()) {
                        System.out.println("🔸 Serial: " + record.getSerial());
                        System.out.println("🔸 NationalNo: " + record.getNationalno());
                        System.out.println("🔸 Emp_Status: " + record.getEmp_status());
                        System.out.println("🔸 Branch Code: " + record.getOffBranchCode());
                        System.out.println("🔸 Office Code: " + record.getOffCode());
                        System.out.println("🔸 App Date: " + record.getAppDate());
                        System.out.println("🔸 Last Update: " + record.getLastUpdate());
                        System.out.println("-----------------------------------");
                    }
                */
                } else {
                    System.out.println("No deleted records returned.");
                }
            } else {
                System.out.println("Delete failed: " + deleteResponse.getMessage());
            }

            return deleteResponse.getStatus();

        } catch (Exception e) {
            e.printStackTrace();
            return "Exception: " + e.getMessage();
        }
    }
}