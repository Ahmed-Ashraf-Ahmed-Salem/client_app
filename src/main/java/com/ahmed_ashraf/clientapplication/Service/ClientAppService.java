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

        rawData.forEach(obj -> System.out.println(
                "Serial: " + obj[0] + ", NationalNo: " + obj[1]
        ));

        return rawData.stream()
                .map(obj -> new SerialNationalDTO(
                        ((BigDecimal) obj[0]).longValue(),
                        (String) obj[1]
                ))
                .collect(Collectors.toList());
    }

}
