package com.ahmed_ashraf.clientapplication.Service;

import com.ahmed_ashraf.clientapplication.Repository.DMClientAppRepository;
import com.ahmed_ashraf.clientapplication.dto.SerialNationalDTO;
import org.springframework.stereotype.Service;


import java.math.BigDecimal;
import java.util.List;
import java.util.stream.Collectors;

@Service
public class ClientAppService {
    private final DMClientAppRepository repository;

    public ClientAppService(DMClientAppRepository repository) {
        this.repository = repository;
    }

    public List<SerialNationalDTO> getAllSerialsAndNationalnos() {
        List<Object[]> rawData = repository.findSerialsAndNIDs();

        System.out.println("Total Records: " + rawData.size());
/*
        rawData.forEach(obj -> System.out.println(
                "Serial: " + obj[0] + ", NationalNo: " + obj[1] + ", Emp_Status: " + obj[2]
        ));
    */
     /*   for(Object o:  rawData){
            System.out.println(o.getClass());
        }*/

        return rawData.stream()
                .map(obj -> new SerialNationalDTO(
                        ((BigDecimal) obj[0]).longValue(),  // serial
                        String.valueOf(obj[1]),   // nationalno
                        String.valueOf(obj[2])   // empStatus ('A' or 'T')
                  //      (String) obj[1],   // national no
                  //      (String) obj[2]    // emp status
                ))
                .collect(Collectors.toList());
    }

}
