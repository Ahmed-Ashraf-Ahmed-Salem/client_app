package com.ahmed_ashraf.clientapplication.dto;

import lombok.AllArgsConstructor;
import lombok.Data;

@Data
@AllArgsConstructor

public class SerialNationalDTO {
    private Long serial;
    private String nationalno;

 /*   public SerialNationalDTO(Long serial, String nationalno) {
        this.serial = serial;
        this.nationalno = nationalno;
    }*/

    // Getters and Setters
    public Long getSerial() {
        return serial;
    }

    public void setSerial(Long serial) {
        this.serial = serial;
    }

    public String getNationalno() {
        return nationalno;
    }

    public void setNationalno(String nationalno) {
        this.nationalno = nationalno;
    }
}
